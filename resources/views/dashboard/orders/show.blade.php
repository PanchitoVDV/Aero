@extends('layouts.app')
@section('title', 'Bestelling #' . $order->id)
@section('header', 'Bestelling #' . $order->id)

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900">Bestelling Details</h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                {{ $order->isPaid() ? 'bg-green-100 text-green-800' : ($order->isPending() ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                {{ $order->getStatusLabel() }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Type</p>
                <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $order->getTypeLabel() }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Pakket</p>
                <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $order->package->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Server</p>
                @if($order->server)
                    <a href="{{ route('servers.show', $order->server) }}" class="text-sm font-medium text-brand-600 hover:underline mt-0.5 block">{{ $order->server->name }}</a>
                @else
                    <p class="text-sm font-medium text-gray-900 mt-0.5">-</p>
                @endif
            </div>
            <div>
                <p class="text-sm text-gray-500">Factureringsperiode</p>
                <p class="text-sm font-medium text-gray-900 mt-0.5">{{ ucfirst($order->billing_cycle === 'monthly' ? 'Maandelijks' : ($order->billing_cycle === 'quarterly' ? 'Per kwartaal' : 'Per jaar')) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Datum</p>
                <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $order->created_at->format('d-m-Y H:i') }}</p>
            </div>
            @if($order->paid_at)
            <div>
                <p class="text-sm text-gray-500">Betaald op</p>
                <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $order->paid_at->format('d-m-Y H:i') }}</p>
            </div>
            @endif
        </div>

        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Bedrag</span>
                    <span class="text-gray-900">&euro;{{ number_format($order->amount, 2, ',', '.') }}</span>
                </div>
                @if($order->setup_fee > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Setup fee</span>
                    <span class="text-gray-900">&euro;{{ number_format($order->setup_fee, 2, ',', '.') }}</span>
                </div>
                @endif
                <div class="flex justify-between text-sm font-semibold pt-2 border-t border-gray-100">
                    <span class="text-gray-900">Totaal</span>
                    <span class="text-gray-900">&euro;{{ number_format($order->total, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        @if($order->mollie_payment_id)
        <div class="mt-4 pt-4 border-t border-gray-200">
            <p class="text-xs text-gray-400">Mollie Payment ID: {{ $order->mollie_payment_id }}</p>
        </div>
        @endif
    </div>

    @if($order->invoice)
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Factuur</h2>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-mono font-medium text-gray-900">{{ $order->invoice->invoice_number }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $order->invoice->created_at->format('d-m-Y') }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm font-semibold text-gray-900">&euro;{{ number_format($order->invoice->total, 2, ',', '.') }}</p>
                <span class="text-xs {{ $order->invoice->status === 'paid' ? 'text-green-600' : 'text-yellow-600' }}">
                    {{ $order->invoice->status === 'paid' ? 'Betaald' : 'Openstaand' }}
                </span>
            </div>
        </div>
    </div>
    @endif

    <div class="flex justify-start">
        <a href="{{ route('orders.index') }}" class="text-sm text-brand-600 hover:underline">Terug naar bestellingen</a>
    </div>
</div>
@endsection
