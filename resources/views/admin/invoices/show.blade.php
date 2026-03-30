@extends('layouts.app')
@section('title', 'Factuur ' . $invoice->invoice_number)
@section('header', 'Factuur ' . $invoice->invoice_number)

@section('header-actions')
<div class="flex items-center gap-2">
    @if($invoice->status !== 'paid')
    <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}">
        @csrf
        <button type="submit" class="bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-green-700 transition">Als betaald markeren</button>
    </form>
    @endif
    <a href="{{ route('admin.invoices.edit', $invoice) }}" class="bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-brand-700 transition">Bewerken</a>
</div>
@endsection

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
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium mt-2
                    {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : ($invoice->status === 'cancelled' ? 'bg-gray-100 text-gray-600' : 'bg-yellow-100 text-yellow-800') }}">
                    {{ $invoice->status === 'paid' ? 'Betaald' : ($invoice->status === 'cancelled' ? 'Geannuleerd' : 'Openstaand') }}
                </span>
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
                        <p class="text-sm font-medium text-gray-900">
                            {{ $invoice->description ?? ($invoice->order?->package?->name ?? 'Server dienst') }}
                        </p>
                        @if($invoice->order)
                        <p class="text-xs text-gray-500">{{ $invoice->order->getTypeLabel() }}
                            @if($invoice->order->server) - {{ $invoice->order->server->name }} @endif
                        </p>
                        @endif
                    </td>
                    <td class="py-4 text-sm text-right text-gray-900">&euro;{{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
                </tr>
                @if($invoice->order && $invoice->order->setup_fee > 0)
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

        {{-- Status & Payment --}}
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Vervaldatum:</span>
                    <span class="font-medium text-gray-900 ml-1">{{ $invoice->due_date->format('d-m-Y') }}</span>
                </div>
                @if($invoice->paid_at)
                <div>
                    <span class="text-gray-500">Betaald op:</span>
                    <span class="font-medium text-gray-900 ml-1">{{ $invoice->paid_at->format('d-m-Y H:i') }}</span>
                </div>
                @endif
            </div>
            @if($invoice->notes)
            <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">Notities:</p>
                <p class="text-sm text-gray-700">{{ $invoice->notes }}</p>
            </div>
            @endif
        </div>
    </div>

    <div class="mt-4 flex justify-between">
        <a href="{{ route('admin.invoices.index') }}" class="text-sm text-brand-600 hover:underline">Terug naar facturen</a>
        @if($invoice->user)
        <a href="{{ route('admin.users.show', $invoice->user) }}" class="text-sm text-brand-600 hover:underline">Bekijk klant</a>
        @endif
    </div>
</div>
@endsection
