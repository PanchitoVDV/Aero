<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Server;
use App\Models\Order;
use App\Models\Package;
use App\Models\ResourcePricing;
use App\Services\VirtFusionService;
use Illuminate\Http\Request;
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
        $usersImported = 0;
        $usersUpdated = 0;
        $serversImported = 0;
        $serversUpdated = 0;
        $errors = [];

        try {
            // VF listing is paginated; collect all pages
            $serverList = [];
            $page = 1;
            do {
                $resp = $virtfusion->getServersPaginated($page);
                $pageData = $resp['data'] ?? [];
                $serverList = array_merge($serverList, $pageData);
                $lastPage = $resp['last_page'] ?? $page;
                $page++;
            } while ($page <= $lastPage);

            if (empty($serverList)) {
                return back()->with('error', 'Geen servers gevonden in VirtFusion. Controleer je API token.');
            }

            $processedUsers = [];

            foreach ($serverList as $vfSrvBasic) {
                try {
                    // Listing only has: id, uuid, name, commissioned, owner (int!), hypervisorId, suspended
                    // We MUST fetch detail for IP, specs, and owner object
                    $vfServerDetail = $virtfusion->getServer($vfSrvBasic['id']);
                    $srv = $vfServerDetail['data'] ?? $vfServerDetail;

                    // ── owner.id is the VF internal user ID ──
                    $ownerData = $srv['owner'] ?? null;
                    if (!is_array($ownerData) || empty($ownerData['email'])) {
                        $errors[] = "Server #{$vfSrvBasic['id']} ({$vfSrvBasic['name']}): owner data ontbreekt";
                        continue;
                    }

                    $vfUserId = $ownerData['id']; // VF internal user id (e.g. 3)

                    if (!isset($processedUsers[$vfUserId])) {
                        $user = User::where('virtfusion_user_id', $vfUserId)->first()
                            ?? User::where('email', $ownerData['email'])->first();

                        if ($user) {
                            $user->update([
                                'virtfusion_user_id' => $vfUserId,
                                'name' => $ownerData['name'] ?? $user->name,
                            ]);
                            $usersUpdated++;
                        } else {
                            $user = User::create([
                                'name' => $ownerData['name'] ?? $ownerData['email'],
                                'email' => $ownerData['email'],
                                'password' => Hash::make(Str::random(24)),
                                'virtfusion_user_id' => $vfUserId,
                            ]);
                            $usersImported++;
                        }

                        $processedUsers[$vfUserId] = $user;
                    }

                    $user = $processedUsers[$vfUserId];

                    // ── IP: network.interfaces[].ipv4[].address ──
                    $ipAddress = $this->extractIpFromServer($srv);

                    // ── Specs: settings.resources.memory/cpuCores, storage[].capacity, cpu.cores ──
                    $packageId = $this->resolvePackageId($srv);
                    if (!$packageId) {
                        $errors[] = "Server '{$srv['name']}': geen pakket";
                        continue;
                    }

                    // ── Status ──
                    [$status, $powerStatus] = $this->resolveServerStatus($srv);

                    // ── OS template name ──
                    $osTemplate = $srv['os']['templateName'] ?? null;

                    // ── Create or update server ──
                    $existingServer = Server::where('virtfusion_server_id', $vfSrvBasic['id'])->first();

                    if ($existingServer) {
                        $existingServer->update([
                            'user_id' => $user->id,
                            'name' => $srv['name'] ?? $existingServer->name,
                            'hostname' => $srv['hostname'] ?? $existingServer->hostname,
                            'ip_address' => $ipAddress ?? $existingServer->ip_address,
                            'os_template' => $osTemplate ?? $existingServer->os_template,
                            'status' => $status,
                            'power_status' => $powerStatus,
                            'package_id' => $packageId,
                        ]);
                        $serversUpdated++;
                    } else {
                        Server::create([
                            'user_id' => $user->id,
                            'package_id' => $packageId,
                            'virtfusion_server_id' => $vfSrvBasic['id'],
                            'name' => $srv['name'] ?? 'Server ' . $vfSrvBasic['id'],
                            'hostname' => $srv['hostname'] ?? null,
                            'os_template' => $osTemplate,
                            'status' => $status,
                            'power_status' => $powerStatus,
                            'ip_address' => $ipAddress,
                            'billing_cycle' => 'monthly',
                            'next_due_date' => now()->addMonth(),
                        ]);
                        $serversImported++;
                    }

                } catch (\Exception $e) {
                    Log::warning("Error syncing server #{$vfSrvBasic['id']}", ['error' => $e->getMessage()]);
                    $errors[] = "Server #{$vfSrvBasic['id']}: {$e->getMessage()}";
                }
            }

            $message = "Sync voltooid: {$usersImported} users nieuw, {$usersUpdated} bijgewerkt, {$serversImported} servers nieuw, {$serversUpdated} bijgewerkt.";
            if (!empty($errors)) {
                $message .= ' | Fouten: ' . implode('; ', array_slice($errors, 0, 5));
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('User sync failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Sync mislukt: ' . $e->getMessage());
        }
    }

    /**
     * Extract IPv4 address from VF server detail.
     * Path: network.interfaces[].ipv4[].address
     */
    private function extractIpFromServer(array $srv): ?string
    {
        $interfaces = $srv['network']['interfaces'] ?? [];

        foreach ($interfaces as $iface) {
            foreach ($iface['ipv4'] ?? [] as $ipEntry) {
                $ip = $ipEntry['address'] ?? null;
                if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return $ip;
                }
            }
        }

        return null;
    }

    /**
     * Resolve or create a Package based on VF server specs.
     * Specs: settings.resources.memory / settings.resources.cpuCores
     * Storage: storage[0].capacity (primary disk)
     * Traffic: traffic.public.currentPeriod.limit
     */
    private function resolvePackageId(array $srv): ?int
    {
        $resources = $srv['settings']['resources'] ?? [];
        $memory = (int) ($resources['memory'] ?? 0);
        $cpuCores = (int) ($resources['cpuCores'] ?? $srv['cpu']['cores'] ?? 0);

        $storage = 0;
        foreach ($srv['storage'] ?? [] as $disk) {
            if (!empty($disk['primary'])) {
                $storage = (int) ($disk['capacity'] ?? 0);
                break;
            }
        }
        if ($storage === 0 && !empty($srv['storage'][0]['capacity'])) {
            $storage = (int) $srv['storage'][0]['capacity'];
        }

        $traffic = (int) ($srv['traffic']['public']['currentPeriod']['limit'] ?? 0);

        // Try matching by specs
        if ($memory > 0 && $cpuCores > 0) {
            $package = Package::where('memory', $memory)
                ->where('cpu_cores', $cpuCores)
                ->where('storage', $storage)
                ->first();
            if ($package) return $package->id;

            $package = Package::where('memory', $memory)
                ->where('cpu_cores', $cpuCores)
                ->first();
            if ($package) return $package->id;
        }

        // Auto-create package from specs
        if ($memory > 0 || $cpuCores > 0) {
            $memLabel = $memory >= 1024 ? round($memory / 1024) . 'GB' : $memory . 'MB';
            $newPkg = Package::create([
                'virtfusion_package_id' => 0,
                'name' => "VPS {$memLabel} - {$cpuCores}vCPU - {$storage}GB",
                'category' => 'vps',
                'memory' => $memory ?: 1024,
                'storage' => $storage ?: 20,
                'cpu_cores' => $cpuCores ?: 1,
                'traffic' => $traffic,
                'price_monthly' => 0,
                'is_active' => true,
            ]);
            return $newPkg->id;
        }

        return Package::first()?->id;
    }

    private function resolveServerStatus(array $srv): array
    {
        $commissionStatus = $srv['commissionStatus'] ?? null;
        $state = $srv['state'] ?? '';

        $status = 'active';
        if (!empty($srv['suspended'])) {
            $status = 'suspended';
        } elseif ($commissionStatus !== null && $commissionStatus < 3) {
            $status = 'building';
        }

        $powerStatus = 'offline';
        if (in_array($state, ['running', 'active'])) {
            $powerStatus = 'online';
        } elseif ($state === 'complete' && $commissionStatus === 3) {
            $powerStatus = 'online';
        }

        return [$status, $powerStatus];
    }

    public function debugVfApi(VirtFusionService $virtfusion)
    {
        $output = [];

        try {
            $serversResponse = $virtfusion->getServers();
            $output['getServers_keys'] = array_keys($serversResponse);
            $serverList = $serversResponse['data'] ?? $serversResponse;
            $output['server_count'] = is_array($serverList) ? count($serverList) : 'not_array';

            if (is_array($serverList) && !empty($serverList)) {
                $first = $serverList[0] ?? $serverList[array_key_first($serverList)] ?? null;
                if ($first) {
                    $output['listing_first_server'] = $first;
                }

                $serverId = $first['id'] ?? null;
                if ($serverId) {
                    try {
                        $detail = $virtfusion->getServer($serverId);
                        $output['getServer_detail'] = $detail;
                    } catch (\Exception $e) {
                        $output['getServer_error'] = $e->getMessage();
                    }
                }
            }
        } catch (\Exception $e) {
            $output['getServers_error'] = $e->getMessage();
        }

        return response()->json($output, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function pricing()
    {
        $resources = ResourcePricing::orderBy('id')->get();

        if ($resources->isEmpty()) {
            (new \Database\Seeders\ResourcePricingSeeder())->run();
            $resources = ResourcePricing::orderBy('id')->get();
        }

        return view('admin.pricing', compact('resources'));
    }

    public function updatePricing(Request $request)
    {
        $data = $request->validate([
            'resources' => 'required|array',
            'resources.*.price_per_unit' => 'required|numeric|min:0',
            'resources.*.min_value' => 'required|integer|min:0',
            'resources.*.max_value' => 'required|integer|min:1',
            'resources.*.step' => 'required|integer|min:1',
            'resources.*.default_value' => 'required|integer|min:0',
        ]);

        foreach ($data['resources'] as $id => $values) {
            ResourcePricing::where('id', $id)->update($values);
        }

        return redirect()->route('admin.pricing')->with('success', 'Prijzen succesvol bijgewerkt.');
    }
}
