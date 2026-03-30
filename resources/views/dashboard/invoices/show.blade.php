@extends('layouts.app')
@section('title', 'Factuur ' . $invoice->invoice_number)
@section('header', 'Factuur ' . $invoice->invoice_number)

@section('content')
<div class="max-w-3xl">
    <div class="bg-white rounded-xl border border-gray-200 p-8">
        {{-- Header --}}
        <div class="flex items-start justify-between mb-8">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-brand-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
                    </div>
                    <div>
                        <span class="text-lg font-bold text-gray-900">Cloudito</span>
                        <span class="text-xs text-gray-400 block">Aero Cloud Services</span>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <h2 class="text-2xl font-bold text-gray-900">FACTUUR</h2>
                <p class="text-sm font-mono text-gray-600 mt-1">{{ $invoice->invoice_number }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ $invoice->created_at->format('d-m-Y') }}</p>
            </div>
        </div>

        {{-- Bill To --}}
        <div class="mb-8">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-2">Factureren aan</p>
            <p class="text-sm font-medium text-gray-900">{{ $invoice->user->name }}</p>
            <p class="text-sm text-gray-600">{{ $invoice->user->email }}</p>
            @if($invoice->user->company)<p class="text-sm text-gray-600">{{ $invoice->user->company }}</p>@endif
            @if($invoice->user->address)<p class="text-sm text-gray-600">{{ $invoice->user->address }}, {{ $invoice->user->postal_code }} {{ $invoice->user->city }}</p>@endif
        </div>

        {{-- Items --}}
        <table class="w-full mb-8">
            <thead>
                <tr class="border-b-2 border-gray-200">
                    <th class="py-3 text-left text-xs font-semibold text-gray-500 uppercase">Omschrijving</th>
                    <th class="py-3 text-right text-xs font-semibold text-gray-500 uppercase">Bedrag</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-gray-100">
                    <td class="py-4">
                        <p class="text-sm font-medium text-gray-900">{{ $invoice->order->package->name ?? 'Server dienst' }}</p>
                        <p class="text-xs text-gray-500">{{ $invoice->order->getTypeLabel() }}
                            @if($invoice->order->server) - {{ $invoice->order->server->name }} @endif
                        </p>
                    </td>
                    <td class="py-4 text-sm text-right text-gray-900">&euro;{{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
                </tr>
                @if($invoice->order->setup_fee > 0)
                <tr class="border-b border-gray-100">
                    <td class="py-4 text-sm text-gray-700">Setup fee</td>
                    <td class="py-4 text-sm text-right text-gray-900">&euro;{{ number_format($invoice->order->setup_fee, 2, ',', '.') }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="border-t-2 border-gray-200 pt-4">
            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-500">Subtotaal</span>
                <span class="text-gray-900">&euro;{{ number_format($invoice->subtotal, 2, ',', '.') }}</span>
            </div>
            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-500">BTW ({{ $invoice->tax_rate }}%)</span>
                <span class="text-gray-900">&euro;{{ number_format($invoice->tax_amount, 2, ',', '.') }}</span>
            </div>
            <div class="flex justify-between text-base font-bold pt-2 border-t border-gray-200">
                <span class="text-gray-900">Totaal</span>
                <span class="text-gray-900">&euro;{{ number_format($invoice->total, 2, ',', '.') }}</span>
            </div>
        </div>

        {{-- Status --}}
        <div class="mt-6 pt-6 border-t border-gray-200 flex items-center justify-between">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                {{ $invoice->status === 'paid' ? 'Betaald' : 'Openstaand' }}
            </span>
            @if($invoice->paid_at)
            <p class="text-xs text-gray-400">Betaald op {{ $invoice->paid_at->format('d-m-Y H:i') }}</p>
            @endif
        </div>
    </div>

    <div class="mt-4 flex justify-start">
        <a href="{{ route('invoices.index') }}" class="text-sm text-brand-600 hover:underline">Terug naar facturen</a>
    </div>
</div>
@endsection
