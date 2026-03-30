@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('header', 'Admin Dashboard')

@section('content')
{{-- Stats --}}
<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Gebruikers</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_users']) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Servers</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_servers']) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Actief</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stats['active_servers']) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Totale Omzet</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">&euro;{{ number_format($stats['total_revenue'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Deze Maand</p>
        <p class="text-2xl font-bold text-brand-600 mt-1">&euro;{{ number_format($stats['monthly_revenue'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Open Orders</p>
        <p class="text-2xl font-bold text-yellow-600 mt-1">{{ number_format($stats['pending_orders']) }}</p>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    {{-- Recent Orders --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Recente Bestellingen</h2>
            <a href="{{ route('admin.orders') }}" class="text-sm text-brand-600 hover:underline">Alles</a>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($recentOrders as $order)
            <div class="px-6 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $order->user->name ?? '-' }}</p>
                    <p class="text-xs text-gray-500">{{ $order->getTypeLabel() }} - {{ $order->package->name ?? '-' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold">&euro;{{ number_format($order->total, 2, ',', '.') }}</p>
                    <p class="text-xs {{ $order->isPaid() ? 'text-green-600' : 'text-yellow-600' }}">{{ $order->getStatusLabel() }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Recent Users --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Nieuwe Gebruikers</h2>
            <a href="{{ route('admin.users') }}" class="text-sm text-brand-600 hover:underline">Alles</a>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($recentUsers as $user)
            <div class="px-6 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                </div>
                <p class="text-xs text-gray-400">{{ $user->created_at->diffForHumans() }}</p>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
