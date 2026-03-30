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
        $usersImported = 0;
        $usersUpdated = 0;
        $serversImported = 0;
        $serversUpdated = 0;
        $errors = [];

        try {
            $vfServersResponse = $virtfusion->getServers();
            $serverList = $vfServersResponse['data'] ?? [];

            if (empty($serverList)) {
                return back()->with('error', 'Geen servers gevonden in VirtFusion. Controleer je API token.');
            }

            Log::info('VF Sync: server list keys (first server)', [
                'keys' => array_keys($serverList[0] ?? []),
                'sample' => array_intersect_key($serverList[0] ?? [], array_flip([
                    'id', 'ownerId', 'name', 'hostname', 'state', 'commissionStatus',
                ])),
            ]);

            $processedUsers = [];

            foreach ($serverList as $vfSrvBasic) {
                try {
                    $vfServerDetail = $virtfusion->getServer($vfSrvBasic['id']);
                    $srv = $vfServerDetail['data'] ?? $vfServerDetail;

                    if ($srv === $vfSrvBasic) {
                        Log::info("VF Sync: getServer returned same as listing for #{$vfSrvBasic['id']}");
                    }

                    Log::info("VF Sync: server #{$vfSrvBasic['id']} detail keys", [
                        'keys' => array_keys($srv),
                        'hasOwner' => isset($srv['owner']),
                        'ownerKeys' => isset($srv['owner']) ? array_keys($srv['owner']) : 'N/A',
                    ]);

                    $ownerId = $srv['ownerId'] ?? $vfSrvBasic['ownerId'] ?? null;
                    if (!$ownerId) {
                        $errors[] = "Server #{$vfSrvBasic['id']}: geen owner";
                        continue;
                    }

                    if (!isset($processedUsers[$ownerId])) {
                        $ownerData = $srv['owner'] ?? null;

                        if (!$ownerData || empty($ownerData['email'])) {
                            $ownerData = $srv['user'] ?? null;
                        }

                        if (!$ownerData || empty($ownerData['email'])) {
                            $extRelId = $ownerData['extRelationId'] ?? $srv['extRelationId'] ?? null;
                            if ($extRelId) {
                                try {
                                    $vfUserResponse = $virtfusion->getUser($extRelId);
                                    $ownerData = $vfUserResponse['data'] ?? $vfUserResponse;
                                } catch (\Exception $e) {
                                    Log::warning("VF Sync: getUser by extRelId {$extRelId} failed", ['error' => $e->getMessage()]);
                                }
                            }
                        }

                        if (!$ownerData || empty($ownerData['email'])) {
                            try {
                                $userServersResp = $virtfusion->getUserServers($ownerId);
                                $userServers = $userServersResp['data'] ?? [];
                                if (!empty($userServers)) {
                                    $firstServer = is_array($userServers[0] ?? null) ? $userServers[0] : $userServers;
                                    $ownerData = $firstServer['owner'] ?? $firstServer['user'] ?? null;
                                }
                            } catch (\Exception $e) {
                                Log::warning("VF Sync: getUserServers({$ownerId}) failed", ['error' => $e->getMessage()]);
                            }
                        }

                        if (empty($ownerData['email'])) {
                            Log::warning("VF Sync: no email for VF user #{$ownerId}", [
                                'ownerData' => $ownerData,
                                'serverKeys' => array_keys($srv),
                            ]);
                            $errors[] = "User #{$ownerId}: geen email gevonden in server response";
                            continue;
                        }

                        $user = User::where('virtfusion_user_id', $ownerId)->first()
                            ?? User::where('email', $ownerData['email'])->first();

                        if ($user) {
                            $user->update([
                                'virtfusion_user_id' => $ownerId,
                                'name' => $ownerData['name'] ?? $user->name,
                            ]);
                            $usersUpdated++;
                        } else {
                            $user = User::create([
                                'name' => $ownerData['name'] ?? $ownerData['email'],
                                'email' => $ownerData['email'],
                                'password' => Hash::make(Str::random(24)),
                                'virtfusion_user_id' => $ownerId,
                            ]);
                            $usersImported++;
                        }

                        $processedUsers[$ownerId] = $user;
                    }

                    $user = $processedUsers[$ownerId];

                    // ── Extract IP from full server details ──
                    $ipAddress = $this->extractIpFromServer($srv);

                    // ── Match or create package based on server specs ──
                    $packageId = $this->resolvePackageId($srv);

                    if (!$packageId) {
                        $errors[] = "Server '{$srv['name']}': geen pakket";
                        continue;
                    }

                    // ── Determine status ──
                    [$status, $powerStatus] = $this->resolveServerStatus($srv);

                    // ── Create or update server ──
                    $existingServer = Server::where('virtfusion_server_id', $vfSrvBasic['id'])->first();

                    if ($existingServer) {
                        $existingServer->update([
                            'name' => $srv['name'] ?? $existingServer->name,
                            'hostname' => $srv['hostname'] ?? $existingServer->hostname,
                            'ip_address' => $ipAddress ?? $existingServer->ip_address,
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
                            'status' => $status,
                            'power_status' => $powerStatus,
                            'ip_address' => $ipAddress,
                            'billing_cycle' => 'monthly',
                            'next_due_date' => now()->addMonth(),
                        ]);
                        $serversImported++;
                    }

                } catch (\Exception $e) {
                    Log::warning("Error syncing server #{$vfSrvBasic['id']}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $errors[] = "Server #{$vfSrvBasic['id']}: {$e->getMessage()}";
                }
            }

            $message = "Sync voltooid: {$usersImported} users nieuw, {$usersUpdated} bijgewerkt, {$serversImported} servers nieuw, {$serversUpdated} bijgewerkt.";
            if (!empty($errors)) {
                $message .= ' | Fouten: ' . implode('; ', array_slice($errors, 0, 5));
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('User sync failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Sync mislukt: ' . $e->getMessage());
        }
    }

    private function extractIpFromServer(array $srv): ?string
    {
        $searchPaths = [
            $srv['network']['interfaces'] ?? null,
            $srv['interfaces'] ?? null,
            $srv['networkInterfaces'] ?? null,
        ];

        foreach ($searchPaths as $interfaces) {
            if (!is_array($interfaces)) continue;
            foreach ($interfaces as $iface) {
                $addrSources = [
                    $iface['ipAddresses'] ?? [],
                    $iface['addresses'] ?? [],
                    $iface['ips'] ?? [],
                ];
                foreach ($addrSources as $addrs) {
                    foreach ($addrs as $addr) {
                        $ip = is_array($addr) ? ($addr['address'] ?? $addr['ip'] ?? null) : $addr;
                        if (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                            return $ip;
                        }
                    }
                }
            }
        }

        return $srv['ip'] ?? $srv['primaryIp'] ?? null;
    }

    private function resolvePackageId(array $srv): ?int
    {
        $memory = $srv['memory'] ?? $srv['ram'] ?? 0;
        $cpuCores = $srv['cpuCores'] ?? $srv['cpu'] ?? $srv['vcpus'] ?? 0;
        $storage = $srv['primaryStorage'] ?? $srv['disk'] ?? $srv['storage'] ?? 0;
        $traffic = $srv['traffic'] ?? $srv['bandwidth'] ?? 0;

        if (isset($srv['packageId'])) {
            $package = Package::where('virtfusion_package_id', $srv['packageId'])->first();
            if ($package) return $package->id;
        }

        if ($memory > 0 || $cpuCores > 0) {
            $package = Package::where('memory', $memory)->where('cpu_cores', $cpuCores)->first();
            if ($package) return $package->id;
        }

        if (isset($srv['packageId'])) {
            $newPkg = Package::create([
                'virtfusion_package_id' => $srv['packageId'],
                'name' => 'VPS ' . ($memory >= 1024 ? round($memory / 1024) . 'GB' : $memory . 'MB') . ' - ' . $cpuCores . 'vCPU',
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
}
