@extends('layouts.app')
@section('title', 'Bestellingen Beheer')
@section('header', 'Alle Bestellingen')

@section('content')
<div class="bg-white rounded-xl border border-gray-200">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Klant</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pakket</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Bedrag</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mollie ID</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Datum</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($orders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $order->id }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $order->user->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $order->getTypeLabel() }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $order->package->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">&euro;{{ number_format($order->total, 2, ',', '.') }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $order->isPaid() ? 'bg-green-100 text-green-800' : ($order->isPending() ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            {{ $order->getStatusLabel() }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ $order->mollie_payment_id ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $order->created_at->format('d-m-Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $orders->links() }}
    </div>
</div>
@endsection
