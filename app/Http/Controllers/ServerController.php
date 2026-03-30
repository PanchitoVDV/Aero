<?php

namespace App\Http\Controllers;

use App\Models\Package;
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
        $packages = Package::active()->orderBy('sort_order')->get();
        return view('dashboard.servers.create', compact('packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
            'name' => ['required', 'string', 'max:100'],
            'hostname' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9.-]+$/'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,yearly'],
            'os_template' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $package = Package::findOrFail($validated['package_id']);

        $amount = $package->getPriceForCycle($validated['billing_cycle']);
        $total = $amount + (float)$package->setup_fee;

        $server = Server::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'name' => $validated['name'],
            'hostname' => $validated['hostname'],
            'os_template' => $validated['os_template'],
            'billing_cycle' => $validated['billing_cycle'],
            'status' => 'pending',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'package_id' => $package->id,
            'type' => 'new',
            'status' => 'pending',
            'amount' => $amount,
            'setup_fee' => $package->setup_fee,
            'total' => $total,
            'billing_cycle' => $validated['billing_cycle'],
            'metadata' => [
                'os_template' => $validated['os_template'],
                'hostname' => $validated['hostname'],
            ],
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'action' => 'server.ordered',
            'description' => "Server '{$server->name}' besteld met pakket {$package->name}",
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

        $currentPackage = $server->package;
        $packages = Package::active()
            ->where('price_monthly', '>', $currentPackage->price_monthly)
            ->where('category', $currentPackage->category)
            ->orderBy('price_monthly')
            ->get();

        return view('dashboard.servers.upgrade', compact('server', 'currentPackage', 'packages'));
    }

    public function processUpgrade(Request $request, Server $server)
    {
        $this->authorizeServer($server);

        $validated = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
        ]);

        $user = Auth::user();
        $newPackage = Package::findOrFail($validated['package_id']);
        $currentPackage = $server->package;

        if ($newPackage->price_monthly <= $currentPackage->price_monthly) {
            return back()->with('error', 'Selecteer een hoger pakket voor een upgrade.');
        }

        $priceDiff = $newPackage->getPriceForCycle($server->billing_cycle) - $currentPackage->getPriceForCycle($server->billing_cycle);

        $order = Order::create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'package_id' => $newPackage->id,
            'type' => 'upgrade',
            'status' => 'pending',
            'amount' => $priceDiff,
            'setup_fee' => 0,
            'total' => $priceDiff,
            'billing_cycle' => $server->billing_cycle,
            'metadata' => [
                'old_package_id' => $currentPackage->id,
                'new_package_id' => $newPackage->id,
            ],
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

    public function downgrade(Server $server)
    {
        $this->authorizeServer($server);

        $currentPackage = $server->package;
        $packages = Package::active()
            ->where('price_monthly', '<', $currentPackage->price_monthly)
            ->where('category', $currentPackage->category)
            ->orderByDesc('price_monthly')
            ->get();

        return view('dashboard.servers.downgrade', compact('server', 'currentPackage', 'packages'));
    }

    public function processDowngrade(Request $request, Server $server)
    {
        $this->authorizeServer($server);

        $validated = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
        ]);

        $user = Auth::user();
        $newPackage = Package::findOrFail($validated['package_id']);
        $currentPackage = $server->package;

        if ($newPackage->price_monthly >= $currentPackage->price_monthly) {
            return back()->with('error', 'Selecteer een lager pakket voor een downgrade.');
        }

        try {
            if ($server->virtfusion_server_id) {
                $this->virtfusion->changeServerPackage(
                    $server->virtfusion_server_id,
                    $newPackage->virtfusion_package_id
                );
            }

            $server->update(['package_id' => $newPackage->id]);

            ActivityLog::create([
                'user_id' => $user->id,
                'server_id' => $server->id,
                'action' => 'server.downgraded',
                'description' => "Server '{$server->name}' gedowngraded van {$currentPackage->name} naar {$newPackage->name}",
                'ip_address' => $request->ip(),
            ]);

            return redirect()->route('servers.show', $server)
                ->with('success', 'Server succesvol gedowngraded naar ' . $newPackage->name);
        } catch (\Exception $e) {
            Log::error('Downgrade failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Downgrade mislukt: ' . $e->getMessage());
        }
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
