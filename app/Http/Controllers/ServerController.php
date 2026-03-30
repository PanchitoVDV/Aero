<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\ResourcePricing;
use App\Models\Server;
use App\Models\Order;
use App\Models\ActivityLog;
use App\Services\VirtFusionService;
use App\Services\MollieService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ServerController extends Controller
{
    public function __construct(
        private VirtFusionService $virtfusion,
        private MollieService $mollie,
    ) {}

    public function index()
    {
        $servers = Auth::user()->servers()->with('package')->latest()->get();
        return view('dashboard.servers.index', compact('servers'));
    }

    public function create()
    {
        $pricing = ResourcePricing::orderBy('id')->get();

        if ($pricing->isEmpty()) {
            (new \Database\Seeders\ResourcePricingSeeder())->run();
            $pricing = ResourcePricing::orderBy('id')->get();
        }

        return view('dashboard.servers.create', compact('pricing'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ram_gb' => ['required', 'integer', 'min:1'],
            'cpu_core' => ['required', 'integer', 'min:1'],
            'storage_gb' => ['required', 'integer', 'min:10'],
            'ipv4' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:100'],
            'hostname' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9.-]+$/'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,yearly'],
            'os_template' => ['required', 'string'],
        ]);

        $pricingAll = ResourcePricing::all()->keyBy('resource_type');
        foreach (['ram_gb', 'cpu_core', 'storage_gb', 'ipv4'] as $type) {
            $rule = $pricingAll->get($type);
            if ($rule && ($validated[$type] < $rule->min_value || $validated[$type] > $rule->max_value)) {
                return back()->withErrors([$type => "{$rule->label} moet tussen {$rule->min_value} en {$rule->max_value} liggen."])->withInput();
            }
        }

        $user = Auth::user();
        $monthlyPrice = ResourcePricing::calculateMonthlyPrice(
            $validated['ram_gb'],
            $validated['cpu_core'],
            $validated['storage_gb'],
            $validated['ipv4']
        );
        $cyclePrice = ResourcePricing::calculateForCycle(
            $validated['ram_gb'],
            $validated['cpu_core'],
            $validated['storage_gb'],
            $validated['ipv4'],
            $validated['billing_cycle']
        );

        $server = Server::create([
            'user_id' => $user->id,
            'package_id' => null,
            'name' => $validated['name'],
            'hostname' => $validated['hostname'],
            'os_template' => $validated['os_template'],
            'billing_cycle' => $validated['billing_cycle'],
            'custom_ram' => $validated['ram_gb'],
            'custom_cpu' => $validated['cpu_core'],
            'custom_storage' => $validated['storage_gb'],
            'custom_ipv4' => $validated['ipv4'],
            'monthly_price' => $monthlyPrice,
            'status' => 'pending',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'package_id' => null,
            'type' => 'new',
            'status' => 'pending',
            'amount' => $cyclePrice,
            'setup_fee' => 0,
            'total' => $cyclePrice,
            'billing_cycle' => $validated['billing_cycle'],
            'metadata' => [
                'os_template' => $validated['os_template'],
                'hostname' => $validated['hostname'],
                'ram_gb' => $validated['ram_gb'],
                'cpu_core' => $validated['cpu_core'],
                'storage_gb' => $validated['storage_gb'],
                'ipv4' => $validated['ipv4'],
                'monthly_price' => $monthlyPrice,
            ],
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'action' => 'server.ordered',
            'description' => "Server '{$server->name}' besteld: {$validated['ram_gb']}GB RAM, {$validated['cpu_core']} vCPU, {$validated['storage_gb']}GB SSD, {$validated['ipv4']} IPv4",
            'ip_address' => $request->ip(),
        ]);

        try {
            $redirectUrl = route('servers.show', $server->id);
            $payment = $this->mollie->createFirstPayment($user, $order, $redirectUrl);
            return redirect($payment->getCheckoutUrl());
        } catch (\Exception $e) {
            Log::error('Mollie payment creation failed', ['error' => $e->getMessage()]);
            $order->update(['status' => 'failed']);
            return back()->with('error', 'Er ging iets mis bij het aanmaken van de betaling. Probeer het opnieuw.');
        }
    }

    public function show(Server $server)
    {
        $this->authorizeServer($server);

        $vfData = null;
        if ($server->virtfusion_server_id) {
            try {
                $vfData = $this->virtfusion->getServer($server->virtfusion_server_id);
                $srv = $vfData['data'] ?? $vfData ?? null;

                if ($srv) {
                    $updates = [];

                    // IP: network.interfaces[].ipv4[].address
                    if (!$server->ip_address) {
                        foreach ($srv['network']['interfaces'] ?? [] as $iface) {
                            foreach ($iface['ipv4'] ?? [] as $ipEntry) {
                                $ip = $ipEntry['address'] ?? null;
                                if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                                    $updates['ip_address'] = $ip;
                                    break 2;
                                }
                            }
                        }
                    }

                    if (!$server->hostname && is_string($srv['hostname'] ?? null) && $srv['hostname']) {
                        $updates['hostname'] = $srv['hostname'];
                    }

                    if (!$server->os_template && isset($srv['os']['templateName'])) {
                        $updates['os_template'] = $srv['os']['templateName'];
                    }

                    $state = $srv['state'] ?? '';
                    $commissionStatus = $srv['commissionStatus'] ?? null;
                    if (in_array($state, ['running', 'active'])) {
                        $updates['power_status'] = 'online';
                    } elseif ($state === 'complete' && $commissionStatus === 3) {
                        $updates['power_status'] = 'online';
                    } else {
                        $updates['power_status'] = 'offline';
                    }

                    if (!empty($updates)) {
                        $server->update($updates);
                        $server->refresh();
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Could not fetch VirtFusion server data', ['error' => $e->getMessage()]);
            }
        }

        $activities = $server->activityLogs()->latest()->take(10)->get();
        return view('dashboard.servers.show', compact('server', 'vfData', 'activities'));
    }

    public function power(Request $request, Server $server)
    {
        $this->authorizeServer($server);

        $validated = $request->validate([
            'action' => ['required', 'in:boot,shutdown,restart,poweroff'],
        ]);

        if (!$server->virtfusion_server_id || !$server->isActive()) {
            return back()->with('error', 'Deze server kan momenteel niet worden beheerd.');
        }

        try {
            $action = $validated['action'];
            match ($action) {
                'boot' => $this->virtfusion->bootServer($server->virtfusion_server_id),
                'shutdown' => $this->virtfusion->shutdownServer($server->virtfusion_server_id),
                'restart' => $this->virtfusion->restartServer($server->virtfusion_server_id),
                'poweroff' => $this->virtfusion->poweroffServer($server->virtfusion_server_id),
            };

            $actionLabels = [
                'boot' => 'opgestart',
                'shutdown' => 'afgesloten',
                'restart' => 'herstart',
                'poweroff' => 'uitgeschakeld',
            ];

            ActivityLog::create([
                'user_id' => Auth::id(),
                'server_id' => $server->id,
                'action' => "server.power.{$action}",
                'description' => "Server '{$server->name}' {$actionLabels[$action]}",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', "Server wordt {$actionLabels[$action]}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Actie mislukt: ' . $e->getMessage());
        }
    }

    public function upgrade(Server $server)
    {
        $this->authorizeServer($server);

        $pricing = ResourcePricing::orderBy('id')->get();
        if ($pricing->isEmpty()) {
            (new \Database\Seeders\ResourcePricingSeeder())->run();
            $pricing = ResourcePricing::orderBy('id')->get();
        }

        $currentSpecs = $this->getServerSpecs($server);
        $currentMonthly = $server->monthly_price ?? ResourcePricing::calculateMonthlyPrice(
            $currentSpecs['ram'], $currentSpecs['cpu'], $currentSpecs['storage'], $currentSpecs['ipv4']
        );

        $basePriceRow = $pricing->firstWhere('resource_type', 'base_price');
        $basePrice = $basePriceRow ? (float) $basePriceRow->price_per_unit : 0;

        return view('dashboard.servers.upgrade', compact('server', 'pricing', 'currentSpecs', 'currentMonthly', 'basePrice'));
    }

    public function processUpgrade(Request $request, Server $server)
    {
        $this->authorizeServer($server);

        $validated = $request->validate([
            'ram_gb' => ['required', 'integer', 'min:1'],
            'cpu_core' => ['required', 'integer', 'min:1'],
            'storage_gb' => ['required', 'integer', 'min:10'],
            'ipv4' => ['required', 'integer', 'min:1'],
        ]);

        $user = Auth::user();
        $currentSpecs = $this->getServerSpecs($server);
        $currentMonthly = $server->monthly_price ?? ResourcePricing::calculateMonthlyPrice(
            $currentSpecs['ram'], $currentSpecs['cpu'], $currentSpecs['storage'], $currentSpecs['ipv4']
        );

        $newMonthly = ResourcePricing::calculateMonthlyPrice(
            $validated['ram_gb'], $validated['cpu_core'], $validated['storage_gb'], $validated['ipv4']
        );

        $diff = round($newMonthly - $currentMonthly, 2);
        $isUpgrade = $diff > 0;

        $server->update([
            'custom_ram' => $validated['ram_gb'],
            'custom_cpu' => $validated['cpu_core'],
            'custom_storage' => $validated['storage_gb'],
            'custom_ipv4' => $validated['ipv4'],
            'monthly_price' => $newMonthly,
            'package_id' => null,
        ]);

        $specStr = "{$validated['ram_gb']}GB RAM, {$validated['cpu_core']} vCPU, {$validated['storage_gb']}GB SSD, {$validated['ipv4']} IPv4";

        if ($isUpgrade) {
            $cycleDiff = match ($server->billing_cycle) {
                'quarterly' => round($diff * 3 * 0.95, 2),
                'yearly' => round($diff * 12 * 0.85, 2),
                default => $diff,
            };

            $order = Order::create([
                'user_id' => $user->id,
                'server_id' => $server->id,
                'package_id' => null,
                'type' => 'upgrade',
                'status' => 'pending',
                'amount' => $cycleDiff,
                'setup_fee' => 0,
                'total' => $cycleDiff,
                'billing_cycle' => $server->billing_cycle,
                'metadata' => [
                    'old_specs' => $currentSpecs,
                    'new_specs' => $validated,
                    'old_monthly' => $currentMonthly,
                    'new_monthly' => $newMonthly,
                ],
            ]);

            ActivityLog::create([
                'user_id' => $user->id,
                'server_id' => $server->id,
                'action' => 'server.upgraded',
                'description' => "Server '{$server->name}' geupgraded naar {$specStr}",
                'ip_address' => $request->ip(),
            ]);

            try {
                $payment = $this->mollie->createFirstPayment($user, $order, route('servers.show', $server->id));
                return redirect($payment->getCheckoutUrl());
            } catch (\Exception $e) {
                Log::error('Upgrade payment failed', ['error' => $e->getMessage()]);
                $order->update(['status' => 'failed']);
                return back()->with('error', 'Betaling kon niet worden aangemaakt.');
            }
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'action' => 'server.downgraded',
            'description' => "Server '{$server->name}' gedowngraded naar {$specStr}",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('servers.show', $server)
            ->with('success', "Server resources aangepast naar {$specStr}. De wijziging gaat in op de volgende factureringsperiode.");
    }

    public function downgrade(Server $server)
    {
        $this->authorizeServer($server);
        return redirect()->route('servers.upgrade', $server);
    }

    public function processDowngrade(Request $request, Server $server)
    {
        return $this->processUpgrade($request, $server);
    }

    private function getServerSpecs(Server $server): array
    {
        if ($server->custom_ram) {
            return [
                'ram' => $server->custom_ram,
                'cpu' => $server->custom_cpu ?? 1,
                'storage' => $server->custom_storage ?? 20,
                'ipv4' => $server->custom_ipv4 ?? 1,
            ];
        }

        $package = $server->package;
        if ($package) {
            return [
                'ram' => (int) ceil(($package->memory ?? 1024) / 1024),
                'cpu' => $package->cpu_cores ?? 1,
                'storage' => $package->storage ?? 20,
                'ipv4' => 1,
            ];
        }

        return ['ram' => 2, 'cpu' => 1, 'storage' => 20, 'ipv4' => 1];
    }

    public function resetPassword(Request $request, Server $server)
    {
        $this->authorizeServer($server);

        if (!$server->virtfusion_server_id || !$server->isActive()) {
            return back()->with('error', 'Server is niet beschikbaar.');
        }

        try {
            $this->virtfusion->resetServerPassword($server->virtfusion_server_id);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'server_id' => $server->id,
                'action' => 'server.password_reset',
                'description' => "Wachtwoord gereset voor server '{$server->name}'",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', 'Wachtwoord wordt gereset. Controleer je e-mail.');
        } catch (\Exception $e) {
            return back()->with('error', 'Wachtwoord reset mislukt.');
        }
    }

    public function console(Server $server)
    {
        $this->authorizeServer($server);

        if (!$server->virtfusion_server_id || !$server->isActive()) {
            return back()->with('error', 'VNC is niet beschikbaar voor deze server.');
        }

        try {
            $vncData = $this->virtfusion->getVncDetails($server->virtfusion_server_id);
            return view('dashboard.servers.console', compact('server', 'vncData'));
        } catch (\Exception $e) {
            return back()->with('error', 'VNC kon niet worden geladen.');
        }
    }

    public function rename(Request $request, Server $server)
    {
        $this->authorizeServer($server);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        if ($server->virtfusion_server_id) {
            try {
                $this->virtfusion->modifyServerName($server->virtfusion_server_id, $validated['name']);
            } catch (\Exception $e) {
                Log::warning('VirtFusion name update failed', ['error' => $e->getMessage()]);
            }
        }

        $server->update(['name' => $validated['name']]);

        return back()->with('success', 'Servernaam bijgewerkt.');
    }

    public function destroy(Request $request, Server $server)
    {
        $this->authorizeServer($server);

        $request->validate([
            'confirm' => ['required', 'in:DELETE'],
        ]);

        try {
            if ($server->virtfusion_server_id) {
                $this->virtfusion->deleteServer($server->virtfusion_server_id);
            }

            $lastOrder = $server->orders()->where('mollie_subscription_id', '!=', null)->latest()->first();
            if ($lastOrder?->mollie_subscription_id) {
                try {
                    $this->mollie->cancelSubscription(Auth::user(), $lastOrder->mollie_subscription_id);
                } catch (\Exception $e) {
                    Log::warning('Could not cancel subscription', ['error' => $e->getMessage()]);
                }
            }

            ActivityLog::create([
                'user_id' => Auth::id(),
                'server_id' => $server->id,
                'action' => 'server.deleted',
                'description' => "Server '{$server->name}' verwijderd",
                'ip_address' => $request->ip(),
            ]);

            $server->update(['status' => 'deleted']);
            $server->delete();

            return redirect()->route('servers.index')->with('success', 'Server wordt verwijderd.');
        } catch (\Exception $e) {
            return back()->with('error', 'Server kon niet worden verwijderd: ' . $e->getMessage());
        }
    }

    private function authorizeServer(Server $server): void
    {
        if ($server->user_id !== Auth::id() && !Auth::user()->is_admin) {
            abort(403);
        }
    }
}
