@extends('layouts.app')
@section('title', 'Facturen Beheer')
@section('header', 'Facturen Beheer')

@section('header-actions')
<a href="{{ route('admin.invoices.create') }}" class="bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-brand-700 transition flex items-center gap-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nieuwe Factuur
</a>
@endsection

@section('content')
{{-- Filters --}}
<div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
    <form method="GET" action="{{ route('admin.invoices.index') }}" class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Zoeken</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Factuurnummer, naam of email..."
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
            <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500">
                <option value="">Alle</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Betaald</option>
                <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Openstaand</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Geannuleerd</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">Filteren</button>
        @if(request()->hasAny(['search', 'status']))
        <a href="{{ route('admin.invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reset</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-200">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Factuurnr</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Klant</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Omschrijving</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Bedrag</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Datum</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Acties</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($invoices as $invoice)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-sm font-mono font-medium text-brand-600 hover:underline">{{ $invoice->invoice_number }}</a>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-sm font-medium text-gray-900">{{ $invoice->user->name ?? '-' }}</p>
                        <p class="text-xs text-gray-500">{{ $invoice->user->email ?? '' }}</p>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $invoice->description ?? ($invoice->order ? $invoice->order->getTypeLabel() : '-') }}
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold text-gray-900 text-right">&euro;{{ number_format($invoice->total, 2, ',', '.') }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : ($invoice->status === 'cancelled' ? 'bg-gray-100 text-gray-600' : 'bg-yellow-100 text-yellow-800') }}">
                            {{ $invoice->status === 'paid' ? 'Betaald' : ($invoice->status === 'cancelled' ? 'Geannuleerd' : 'Openstaand') }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $invoice->created_at->format('d-m-Y') }}</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.invoices.edit', $invoice) }}" class="text-xs text-brand-600 hover:underline">Bewerken</a>
                            @if($invoice->status !== 'paid')
                            <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-green-600 hover:underline">Betaald</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">Geen facturen gevonden.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $invoices->links() }}
    </div>
    @endif
</div>
@endsection
