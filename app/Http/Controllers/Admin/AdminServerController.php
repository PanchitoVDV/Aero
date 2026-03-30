<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ActivityLog;
use App\Services\VirtFusionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminServerController extends Controller
{
    public function __construct(
        private VirtFusionService $virtfusion,
    ) {}

    public function show(Server $server)
    {
        $server->load(['user', 'package', 'activityLogs' => fn($q) => $q->latest()->take(20)]);

        $vfData = null;
        if ($server->virtfusion_server_id) {
            try {
                $vfData = $this->virtfusion->getServer($server->virtfusion_server_id);
                $srv = $vfData['data'] ?? $vfData ?? null;

                if ($srv) {
                    $updates = [];

                    foreach ($srv['network']['interfaces'] ?? [] as $iface) {
                        foreach ($iface['ipv4'] ?? [] as $ipEntry) {
                            $ip = $ipEntry['address'] ?? null;
                            if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                                $updates['ip_address'] = $ip;
                                break 2;
                            }
                        }
                    }

                    if (is_string($srv['hostname'] ?? null) && $srv['hostname']) {
                        $updates['hostname'] = $srv['hostname'];
                    }
                    if (isset($srv['os']['templateName'])) {
                        $updates['os_template'] = $srv['os']['templateName'];
                    }

                    $state = $srv['state'] ?? '';
                    $cs = $srv['commissionStatus'] ?? null;
                    $updates['power_status'] = in_array($state, ['running', 'active']) || ($state === 'complete' && $cs === 3)
                        ? 'online' : 'offline';

                    $server->update($updates);
                    $server->refresh();
                }
            } catch (\Exception $e) {
                Log::warning('Admin: could not fetch VF server', ['error' => $e->getMessage()]);
            }
        }

        return view('admin.servers.show', compact('server', 'vfData'));
    }

    public function power(Request $request, Server $server)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:boot,shutdown,restart,poweroff'],
        ]);

        if (!$server->virtfusion_server_id) {
            return back()->with('error', 'Server heeft geen VirtFusion koppeling.');
        }

        try {
            $action = $validated['action'];
            match ($action) {
                'boot' => $this->virtfusion->bootServer($server->virtfusion_server_id),
                'shutdown' => $this->virtfusion->shutdownServer($server->virtfusion_server_id),
                'restart' => $this->virtfusion->restartServer($server->virtfusion_server_id),
                'poweroff' => $this->virtfusion->poweroffServer($server->virtfusion_server_id),
            };

            $labels = ['boot' => 'opgestart', 'shutdown' => 'afgesloten', 'restart' => 'herstart', 'poweroff' => 'uitgeschakeld'];

            ActivityLog::create([
                'user_id' => Auth::id(),
                'server_id' => $server->id,
                'action' => "admin.server.power.{$action}",
                'description' => "Admin: Server '{$server->name}' {$labels[$action]}",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', "Server wordt {$labels[$action]}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Power actie mislukt: ' . $e->getMessage());
        }
    }

    public function suspend(Request $request, Server $server)
    {
        if (!$server->virtfusion_server_id) {
            return back()->with('error', 'Server heeft geen VirtFusion koppeling.');
        }

        try {
            $this->virtfusion->suspendServer($server->virtfusion_server_id);
            $server->update(['status' => 'suspended', 'suspended_at' => now(), 'suspension_reason' => 'Admin actie']);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'server_id' => $server->id,
                'action' => 'admin.server.suspended',
                'description' => "Admin: Server '{$server->name}' gesuspend",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', 'Server is gesuspend.');
        } catch (\Exception $e) {
            return back()->with('error', 'Suspenderen mislukt: ' . $e->getMessage());
        }
    }

    public function unsuspend(Request $request, Server $server)
    {
        if (!$server->virtfusion_server_id) {
            return back()->with('error', 'Server heeft geen VirtFusion koppeling.');
        }

        try {
            $this->virtfusion->unsuspendServer($server->virtfusion_server_id);
            $server->update(['status' => 'active', 'suspended_at' => null, 'suspension_reason' => null]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'server_id' => $server->id,
                'action' => 'admin.server.unsuspended',
                'description' => "Admin: Server '{$server->name}' unsuspended",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', 'Server is weer actief.');
        } catch (\Exception $e) {
            return back()->with('error', 'Unsuspend mislukt: ' . $e->getMessage());
        }
    }

    public function rename(Request $request, Server $server)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        if ($server->virtfusion_server_id) {
            try {
                $this->virtfusion->modifyServerName($server->virtfusion_server_id, $validated['name']);
            } catch (\Exception $e) {
                Log::warning('Admin: VF name update failed', ['error' => $e->getMessage()]);
            }
        }

        $server->update(['name' => $validated['name']]);
        return back()->with('success', 'Servernaam bijgewerkt.');
    }

    public function resetPassword(Request $request, Server $server)
    {
        if (!$server->virtfusion_server_id) {
            return back()->with('error', 'Server heeft geen VirtFusion koppeling.');
        }

        try {
            $this->virtfusion->resetServerPassword($server->virtfusion_server_id);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'server_id' => $server->id,
                'action' => 'admin.server.password_reset',
                'description' => "Admin: Wachtwoord gereset voor '{$server->name}'",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', 'Server wachtwoord wordt gereset.');
        } catch (\Exception $e) {
            return back()->with('error', 'Wachtwoord reset mislukt: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, Server $server)
    {
        $request->validate([
            'confirm' => ['required', 'in:DELETE'],
        ]);

        try {
            if ($server->virtfusion_server_id) {
                $this->virtfusion->deleteServer($server->virtfusion_server_id);
            }

            ActivityLog::create([
                'user_id' => Auth::id(),
                'server_id' => $server->id,
                'action' => 'admin.server.deleted',
                'description' => "Admin: Server '{$server->name}' verwijderd",
                'ip_address' => $request->ip(),
            ]);

            $server->update(['status' => 'deleted']);
            $server->delete();

            return redirect()->route('admin.servers')->with('success', 'Server wordt verwijderd.');
        } catch (\Exception $e) {
            return back()->with('error', 'Verwijderen mislukt: ' . $e->getMessage());
        }
    }

    public function console(Server $server)
    {
        if (!$server->virtfusion_server_id) {
            return back()->with('error', 'Server heeft geen VirtFusion koppeling.');
        }

        try {
            $vncData = $this->virtfusion->getVncDetails($server->virtfusion_server_id);
            return view('admin.servers.console', compact('server', 'vncData'));
        } catch (\Exception $e) {
            return back()->with('error', 'VNC kon niet worden geladen: ' . $e->getMessage());
        }
    }
}
