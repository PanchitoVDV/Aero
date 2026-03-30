<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Server;
use App\Models\ActivityLog;
use App\Services\MollieService;
use App\Services\VirtFusionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private MollieService $mollie,
        private VirtFusionService $virtfusion,
    ) {}

    public function mollie(Request $request)
    {
        $paymentId = $request->input('id');
        if (!$paymentId) {
            return response('Missing payment ID', 400);
        }

        try {
            $result = $this->mollie->handleWebhook($paymentId);

            if ($result['status'] === 'paid' && isset($result['order_id'])) {
                $this->processPayment($result['order_id']);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', ['error' => $e->getMessage()]);
            return response('Error', 500);
        }
    }

    private function processPayment(int $orderId): void
    {
        $order = Order::with(['user', 'server', 'package'])->find($orderId);
        if (!$order) return;

        match ($order->type) {
            'new' => $this->provisionNewServer($order),
            'upgrade' => $this->processUpgrade($order),
            'renewal' => $this->processRenewal($order),
            default => null,
        };
    }

    private function provisionNewServer(Order $order): void
    {
        $server = $order->server;
        $package = $order->package;
        $user = $order->user;

        if (!$server) return;

        try {
            $server->update(['status' => 'building']);

            if (!$user->virtfusion_user_id) {
                $vfUser = $this->virtfusion->createUser($user->name, $user->email, $user->id);
                $user->update(['virtfusion_user_id' => $vfUser['data']['id'] ?? null]);
            }

            $vfPackageId = $package?->virtfusion_package_id;
            if (!$vfPackageId && $server->custom_ram) {
                $vfPackageId = $this->findClosestVfPackage($server);
            }

            if (!$vfPackageId) {
                throw new \Exception('Geen VirtFusion package gevonden voor deze configuratie');
            }

            $ipv4Count = $server->custom_ipv4 ?? ($package ? 1 : 1);

            $vfServer = $this->virtfusion->createServer(
                $vfPackageId,
                $user->virtfusion_user_id,
                config('virtfusion.hypervisor_group_id'),
                ['ipv4' => $ipv4Count],
            );

            $vfServerId = $vfServer['data']['id'] ?? null;
            if (!$vfServerId) {
                throw new \Exception('Geen server ID ontvangen van VirtFusion');
            }

            $server->update(['virtfusion_server_id' => $vfServerId]);

            $osTemplateId = (int)($order->metadata['os_template'] ?? 1);
            $buildResult = $this->virtfusion->buildServer($vfServerId, $osTemplateId, [
                'name' => $server->name,
                'hostname' => $server->hostname ?? $server->name . '.cloudito.nl',
            ]);

            $ipAddress = $buildResult['data']['network']['interfaces'][0]['ipAddresses'][0]['address'] ?? null;

            $nextDue = match ($order->billing_cycle) {
                'quarterly' => now()->addMonths(3),
                'yearly' => now()->addYear(),
                default => now()->addMonth(),
            };

            $server->update([
                'status' => 'active',
                'power_status' => 'online',
                'ip_address' => $ipAddress,
                'next_due_date' => $nextDue,
            ]);

            $interval = $this->mollie->getBillingInterval($order->billing_cycle);
            try {
                $this->mollie->createSubscription($user, $order, $interval);
            } catch (\Exception $e) {
                Log::warning('Subscription creation failed, will retry on mandate webhook', [
                    'error' => $e->getMessage(),
                    'order_id' => $order->id,
                ]);
                $order->update(['subscription_pending' => true]);
            }

            ActivityLog::create([
                'user_id' => $user->id,
                'server_id' => $server->id,
                'action' => 'server.provisioned',
                'description' => "Server '{$server->name}' succesvol aangemaakt",
            ]);

        } catch (\Exception $e) {
            Log::error('Server provisioning failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            $server->update(['status' => 'error']);

            ActivityLog::create([
                'user_id' => $user->id,
                'server_id' => $server->id,
                'action' => 'server.provision_failed',
                'description' => "Server provisioning mislukt: {$e->getMessage()}",
            ]);
        }
    }

    private function findClosestVfPackage(Server $server): ?int
    {
        try {
            $result = $this->virtfusion->getPackages();
            $packages = $result['data'] ?? [];

            $ramMb = ($server->custom_ram ?? 2) * 1024;
            $cpu = $server->custom_cpu ?? 1;
            $storage = $server->custom_storage ?? 20;

            $best = null;
            $bestScore = PHP_INT_MAX;

            foreach ($packages as $pkg) {
                if (!($pkg['enabled'] ?? true)) continue;
                if (($pkg['memory'] ?? 0) < $ramMb) continue;
                if (($pkg['cpuCores'] ?? 0) < $cpu) continue;
                if (($pkg['primaryStorage'] ?? 0) < $storage) continue;

                $score = ($pkg['memory'] - $ramMb) + (($pkg['cpuCores'] - $cpu) * 1024) + (($pkg['primaryStorage'] - $storage) * 10);
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $best = $pkg['id'];
                }
            }

            return $best;
        } catch (\Exception $e) {
            Log::error('Could not find matching VF package', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function processUpgrade(Order $order): void
    {
        $server = $order->server;
        $newPackage = $order->package;

        if (!$server) return;

        try {
            if ($newPackage && $server->virtfusion_server_id) {
                $this->virtfusion->changeServerPackage(
                    $server->virtfusion_server_id,
                    $newPackage->virtfusion_package_id
                );
                $server->update(['package_id' => $newPackage->id]);
            } elseif ($server->virtfusion_server_id && !empty($order->metadata['new_specs'])) {
                $vfPackageId = $this->findClosestVfPackage($server);
                if ($vfPackageId) {
                    $this->virtfusion->changeServerPackage($server->virtfusion_server_id, $vfPackageId);
                }
            }

            $oldSubscription = $server->orders()
                ->whereNotNull('mollie_subscription_id')
                ->latest()
                ->first();

            if ($oldSubscription?->mollie_subscription_id) {
                try {
                    $this->mollie->cancelSubscription($order->user, $oldSubscription->mollie_subscription_id);
                } catch (\Exception $e) {
                    Log::warning('Could not cancel old subscription', ['error' => $e->getMessage()]);
                }
            }

            $interval = $this->mollie->getBillingInterval($order->billing_cycle);
            try {
                $newMonthly = $server->monthly_price ?? $order->amount;
                $subscriptionOrder = $order->replicate();
                $subscriptionOrder->amount = $newMonthly;
                $subscriptionOrder->total = $newMonthly;
                $subscriptionOrder->save();

                $this->mollie->createSubscription($order->user, $subscriptionOrder, $interval);
            } catch (\Exception $e) {
                Log::warning('New subscription after upgrade failed', ['error' => $e->getMessage()]);
            }

            $specs = $order->metadata['new_specs'] ?? null;
            $desc = $specs
                ? "{$specs['ram_gb']}GB RAM, {$specs['cpu_core']} vCPU, {$specs['storage_gb']}GB SSD, {$specs['ipv4']} IPv4"
                : ($newPackage ? $newPackage->name : 'custom configuratie');

            ActivityLog::create([
                'user_id' => $order->user_id,
                'server_id' => $server->id,
                'action' => 'server.upgraded',
                'description' => "Server '{$server->name}' geüpgraded naar {$desc}",
            ]);
        } catch (\Exception $e) {
            Log::error('Server upgrade failed', ['error' => $e->getMessage()]);
        }
    }

    private function processRenewal(Order $order): void
    {
        $server = $order->server;
        if (!$server) return;

        $nextDue = match ($order->billing_cycle) {
            'quarterly' => now()->addMonths(3),
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };

        $server->update(['next_due_date' => $nextDue]);

        if ($server->isSuspended()) {
            try {
                if ($server->virtfusion_server_id) {
                    $this->virtfusion->unsuspendServer($server->virtfusion_server_id);
                }
                $server->update([
                    'status' => 'active',
                    'suspended_at' => null,
                    'suspension_reason' => null,
                ]);
            } catch (\Exception $e) {
                Log::error('Unsuspend after renewal failed', ['error' => $e->getMessage()]);
            }
        }
    }
}
