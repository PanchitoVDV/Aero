@extends('layouts.app')
@section('title', 'Facturen')
@section('header', 'Mijn Facturen')

@section('content')
<div class="bg-white rounded-xl border border-gray-200">
    @if($invoices->isEmpty())
    <div class="px-6 py-12 text-center">
        <p class="text-gray-500">Je hebt nog geen facturen.</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Factuurnr.</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Beschrijving</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subtotaal</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">BTW (21%)</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Totaal</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Datum</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($invoices as $invoice)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm font-mono font-medium text-gray-900">{{ $invoice->invoice_number }}</td>
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $invoice->order->package->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-700">&euro;{{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-700">&euro;{{ number_format($invoice->tax_amount, 2, ',', '.') }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">&euro;{{ number_format($invoice->total, 2, ',', '.') }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $invoice->status === 'paid' ? 'Betaald' : 'Openstaand' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $invoice->created_at->format('d-m-Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $invoices->links() }}
    </div>
    @endif
</div>
@endsection
