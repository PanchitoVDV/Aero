<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Invoice;
use Mollie\Api\MollieApiClient;
use Illuminate\Support\Facades\Log;

class MollieService
{
    private MollieApiClient $mollie;

    public function __construct()
    {
        $this->mollie = new MollieApiClient();
        $this->mollie->setApiKey(config('mollie.key'));
    }

    public function getClient(): MollieApiClient
    {
        return $this->mollie;
    }

    // ── Customer Management ──────────────────────────────────────────
    public function createCustomer(User $user): string
    {
        $customer = $this->mollie->customers->create([
            'name' => $user->name,
            'email' => $user->email,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        $user->update(['mollie_customer_id' => $customer->id]);

        return $customer->id;
    }

    public function getOrCreateCustomer(User $user): string
    {
        if ($user->mollie_customer_id) {
            return $user->mollie_customer_id;
        }

        return $this->createCustomer($user);
    }

    // ── One-time Payment ─────────────────────────────────────────────
    public function createPayment(Order $order, string $description, string $redirectUrl = null): \Mollie\Api\Resources\Payment
    {
        $customerId = $this->getOrCreateCustomer($order->user);

        $paymentData = [
            'amount' => [
                'currency' => config('mollie.currency', 'EUR'),
                'value' => number_format($order->total, 2, '.', ''),
            ],
            'customerId' => $customerId,
            'description' => $description,
            'redirectUrl' => $redirectUrl ?? config('mollie.redirect_url'),
            'webhookUrl' => config('mollie.webhook_url'),
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'type' => $order->type,
            ],
            'sequenceType' => 'first',
            'locale' => config('mollie.locale', 'nl_NL'),
        ];

        $payment = $this->mollie->payments->create($paymentData);

        $order->update(['mollie_payment_id' => $payment->id]);

        return $payment;
    }

    // ── First Payment (for mandate) ──────────────────────────────────
    public function createFirstPayment(User $user, Order $order, string $redirectUrl = null): \Mollie\Api\Resources\Payment
    {
        $customerId = $this->getOrCreateCustomer($user);

        $payment = $this->mollie->payments->create([
            'amount' => [
                'currency' => config('mollie.currency', 'EUR'),
                'value' => number_format($order->total, 2, '.', ''),
            ],
            'customerId' => $customerId,
            'sequenceType' => 'first',
            'description' => "Cloudito - {$order->getTypeLabel()} #{$order->id}",
            'redirectUrl' => $redirectUrl ?? config('mollie.redirect_url'),
            'webhookUrl' => config('mollie.webhook_url'),
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'type' => $order->type,
            ],
            'locale' => config('mollie.locale', 'nl_NL'),
        ]);

        $order->update(['mollie_payment_id' => $payment->id]);

        return $payment;
    }

    // ── Subscription ─────────────────────────────────────────────────
    public function createSubscription(User $user, Order $order, string $interval = '1 month'): \Mollie\Api\Resources\Subscription
    {
        $customerId = $this->getOrCreateCustomer($user);
        $customer = $this->mollie->customers->get($customerId);

        $subscription = $customer->createSubscription([
            'amount' => [
                'currency' => config('mollie.currency', 'EUR'),
                'value' => number_format($order->amount, 2, '.', ''),
            ],
            'interval' => $interval,
            'description' => "Cloudito Server #{$order->server_id} - " . ucfirst($order->billing_cycle),
            'webhookUrl' => config('mollie.webhook_url'),
            'metadata' => [
                'order_id' => $order->id,
                'server_id' => $order->server_id,
                'user_id' => $user->id,
            ],
        ]);

        $order->update(['mollie_subscription_id' => $subscription->id]);

        return $subscription;
    }

    public function cancelSubscription(User $user, string $subscriptionId): void
    {
        $customer = $this->mollie->customers->get($user->mollie_customer_id);
        $customer->cancelSubscription($subscriptionId);
    }

    // ── Payment Status ───────────────────────────────────────────────
    public function getPayment(string $paymentId): \Mollie\Api\Resources\Payment
    {
        return $this->mollie->payments->get($paymentId);
    }

    public function handleWebhook(string $paymentId): array
    {
        $payment = $this->getPayment($paymentId);

        $orderId = $payment->metadata->order_id ?? null;
        if (!$orderId) {
            Log::warning('Mollie webhook: geen order_id in metadata', ['payment_id' => $paymentId]);
            return ['status' => 'ignored'];
        }

        $order = Order::find($orderId);
        if (!$order) {
            Log::warning('Mollie webhook: order niet gevonden', ['order_id' => $orderId]);
            return ['status' => 'not_found'];
        }

        if ($payment->isPaid()) {
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            if ($payment->hasSequenceTypeFirst() && $payment->mandateId) {
                $order->user->update(['mollie_mandate_id' => $payment->mandateId]);
            }

            $this->createInvoice($order);

            return ['status' => 'paid', 'order_id' => $order->id];
        }

        if ($payment->isFailed()) {
            $order->update(['status' => 'failed']);
            return ['status' => 'failed', 'order_id' => $order->id];
        }

        if ($payment->isExpired()) {
            $order->update(['status' => 'cancelled']);
            return ['status' => 'expired', 'order_id' => $order->id];
        }

        if ($payment->isCanceled()) {
            $order->update(['status' => 'cancelled']);
            return ['status' => 'cancelled', 'order_id' => $order->id];
        }

        return ['status' => 'pending'];
    }

    // ── Invoice Generation ───────────────────────────────────────────
    private function createInvoice(Order $order): Invoice
    {
        $taxRate = 21.00; // BTW
        $subtotal = $order->total / (1 + ($taxRate / 100));
        $taxAmount = $order->total - $subtotal;

        return Invoice::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'invoice_number' => Invoice::generateNumber(),
            'status' => 'paid',
            'subtotal' => round($subtotal, 2),
            'tax_rate' => $taxRate,
            'tax_amount' => round($taxAmount, 2),
            'total' => $order->total,
            'currency' => $order->currency,
            'due_date' => now(),
            'paid_at' => now(),
        ]);
    }

    public function getBillingInterval(string $cycle): string
    {
        return match ($cycle) {
            'quarterly' => '3 months',
            'yearly' => '12 months',
            default => '1 month',
        };
    }
}
