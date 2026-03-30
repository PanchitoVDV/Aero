@extends('layouts.app')
@section('title', 'Bestellingen')
@section('header', 'Mijn Bestellingen')

@section('content')
<div class="bg-white rounded-xl border border-gray-200">
    @if($orders->isEmpty())
    <div class="px-6 py-12 text-center">
        <p class="text-gray-500">Je hebt nog geen bestellingen geplaatst.</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pakket</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Bedrag</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Datum</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($orders as $order)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $order->id }}</td>
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $order->getTypeLabel() }}</td>
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $order->package->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">&euro;{{ number_format($order->total, 2, ',', '.') }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $order->isPaid() ? 'bg-green-100 text-green-800' : ($order->isPending() ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            {{ $order->getStatusLabel() }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $order->created_at->format('d-m-Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
