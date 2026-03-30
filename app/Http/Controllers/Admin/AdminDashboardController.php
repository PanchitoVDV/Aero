<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Server;
use App\Models\Order;
use App\Models\Package;
use App\Services\VirtFusionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_servers' => Server::count(),
            'active_servers' => Server::where('status', 'active')->count(),
            'total_revenue' => Order::where('status', 'paid')->sum('total'),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'monthly_revenue' => Order::where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->sum('total'),
        ];

        $recentOrders = Order::with(['user', 'package'])->latest()->take(10)->get();
        $recentUsers = User::latest()->take(10)->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'recentUsers'));
    }

    public function users()
    {
        $users = User::withCount('servers')->latest()->paginate(25);
        return view('admin.users', compact('users'));
    }

    public function servers()
    {
        $servers = Server::with(['user', 'package'])->latest()->paginate(25);
        return view('admin.servers', compact('servers'));
    }

    public function packages()
    {
        $packages = Package::orderBy('sort_order')->get();
        return view('admin.packages.index', compact('packages'));
    }

    public function createPackage()
    {
        return view('admin.packages.create');
    }

    public function storePackage(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'virtfusion_package_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'in:vps,dedicated,game'],
            'memory' => ['required', 'integer', 'min:256'],
            'storage' => ['required', 'integer', 'min:5'],
            'cpu_cores' => ['required', 'integer', 'min:1'],
            'traffic' => ['required', 'integer', 'min:0'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_quarterly' => ['nullable', 'numeric', 'min:0'],
            'price_yearly' => ['nullable', 'numeric', 'min:0'],
            'setup_fee' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        Package::create($validated);

        return redirect()->route('admin.packages')->with('success', 'Pakket aangemaakt.');
    }

    public function editPackage(Package $package)
    {
        return view('admin.packages.edit', compact('package'));
    }

    public function updatePackage(\Illuminate\Http\Request $request, Package $package)
    {
        $validated = $request->validate([
            'virtfusion_package_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'in:vps,dedicated,game'],
            'memory' => ['required', 'integer', 'min:256'],
            'storage' => ['required', 'integer', 'min:5'],
            'cpu_cores' => ['required', 'integer', 'min:1'],
            'traffic' => ['required', 'integer', 'min:0'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_quarterly' => ['nullable', 'numeric', 'min:0'],
            'price_yearly' => ['nullable', 'numeric', 'min:0'],
            'setup_fee' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        $package->update($validated);

        return redirect()->route('admin.packages')->with('success', 'Pakket bijgewerkt.');
    }

    public function syncPackages(VirtFusionService $virtfusion)
    {
        try {
            $vfPackages = $virtfusion->getPackages();

            foreach ($vfPackages['data'] ?? [] as $vfPkg) {
                Package::updateOrCreate(
                    ['virtfusion_package_id' => $vfPkg['id']],
                    [
                        'name' => $vfPkg['name'],
                        'memory' => $vfPkg['memory'],
                        'storage' => $vfPkg['primaryStorage'],
                        'cpu_cores' => $vfPkg['cpuCores'],
                        'traffic' => $vfPkg['traffic'],
                        'network_speed_in' => $vfPkg['primaryNetworkSpeedIn'] ?? 0,
                        'network_speed_out' => $vfPkg['primaryNetworkSpeedOut'] ?? 0,
                        'price_monthly' => 0, // Must be set manually
                    ]
                );
            }

            return back()->with('success', 'Pakketten gesynchroniseerd met VirtFusion. Stel de prijzen in.');
        } catch (\Exception $e) {
            return back()->with('error', 'Synchronisatie mislukt: ' . $e->getMessage());
        }
    }

    public function orders()
    {
        $orders = Order::with(['user', 'package', 'server'])->latest()->paginate(25);
        return view('admin.orders', compact('orders'));
    }

    public function syncUsers(VirtFusionService $virtfusion)
    {
        $imported = 0;
        $updated = 0;
        $serversImported = 0;
        $errors = [];

        try {
            $vfServers = $virtfusion->getServers();
            $serverList = $vfServers['data'] ?? [];

            $userIds = collect($serverList)->pluck('ownerId')->unique();

            foreach ($userIds as $vfUserId) {
                try {
                    $vfServerData = $virtfusion->getServer(
                        collect($serverList)->where('ownerId', $vfUserId)->first()['id']
                    );
                    $serverData = $vfServerData['data'] ?? $vfServerData;

                    $ownerData = $serverData['owner'] ?? null;

                    if (!$ownerData || empty($ownerData['email'])) {
                        try {
                            $vfUserResponse = $virtfusion->getUser($vfUserId);
                            $ownerData = $vfUserResponse['data'] ?? $vfUserResponse;
                        } catch (\Exception $e) {
                            Log::warning("Could not fetch VF user {$vfUserId}", ['error' => $e->getMessage()]);
                            continue;
                        }
                    }

                    if (empty($ownerData['email'])) {
                        continue;
                    }

                    $user = User::where('virtfusion_user_id', $vfUserId)
                        ->orWhere('email', $ownerData['email'])
                        ->first();

                    if ($user) {
                        $user->update(['virtfusion_user_id' => $vfUserId]);
                        $updated++;
                    } else {
                        $user = User::create([
                            'name' => $ownerData['name'] ?? $ownerData['email'],
                            'email' => $ownerData['email'],
                            'password' => Hash::make(Str::random(24)),
                            'virtfusion_user_id' => $vfUserId,
                        ]);
                        $imported++;
                    }

                    $userServers = collect($serverList)->where('ownerId', $vfUserId);
                    foreach ($userServers as $vfSrv) {
                        $existingServer = Server::where('virtfusion_server_id', $vfSrv['id'])->first();
                        if ($existingServer) {
                            continue;
                        }

                        $packageId = null;
                        if (isset($vfSrv['packageId'])) {
                            $package = Package::where('virtfusion_package_id', $vfSrv['packageId'])->first();
                            $packageId = $package?->id;
                        }

                        if (!$packageId) {
                            $packageId = Package::first()?->id;
                        }

                        if (!$packageId) {
                            $errors[] = "Server '{$vfSrv['name']}': geen pakket gevonden";
                            continue;
                        }

                        $ipAddress = null;
                        if (isset($vfSrv['network']['interfaces'])) {
                            foreach ($vfSrv['network']['interfaces'] as $iface) {
                                if (!empty($iface['ipAddresses'])) {
                                    $ipAddress = $iface['ipAddresses'][0]['address'] ?? null;
                                    break;
                                }
                            }
                        }

                        $status = 'active';
                        if (isset($vfSrv['suspended']) && $vfSrv['suspended']) {
                            $status = 'suspended';
                        } elseif (isset($vfSrv['state'])) {
                            $status = match ($vfSrv['state']) {
                                'running', 'active' => 'active',
                                'stopped', 'shutoff' => 'active',
                                'suspended' => 'suspended',
                                'building' => 'building',
                                default => 'active',
                            };
                        }

                        $powerStatus = 'offline';
                        if (isset($vfSrv['state'])) {
                            $powerStatus = in_array($vfSrv['state'], ['running', 'active']) ? 'online' : 'offline';
                        }

                        Server::create([
                            'user_id' => $user->id,
                            'package_id' => $packageId,
                            'virtfusion_server_id' => $vfSrv['id'],
                            'name' => $vfSrv['name'] ?? 'Server ' . $vfSrv['id'],
                            'hostname' => $vfSrv['hostname'] ?? null,
                            'status' => $status,
                            'power_status' => $powerStatus,
                            'ip_address' => $ipAddress,
                            'billing_cycle' => 'monthly',
                            'next_due_date' => now()->addMonth(),
                        ]);

                        $serversImported++;
                    }
                } catch (\Exception $e) {
                    Log::warning("Error syncing VF user {$vfUserId}", ['error' => $e->getMessage()]);
                    $errors[] = "User {$vfUserId}: {$e->getMessage()}";
                }
            }

            $message = "Sync voltooid: {$imported} users geimporteerd, {$updated} bijgewerkt, {$serversImported} servers geimporteerd.";
            if (!empty($errors)) {
                $message .= ' Waarschuwingen: ' . implode('; ', array_slice($errors, 0, 5));
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('User sync failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Sync mislukt: ' . $e->getMessage());
        }
    }
}
