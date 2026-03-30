@extends('layouts.app')
@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('header-actions')
<a href="{{ route('servers.create') }}" class="bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-brand-700 transition flex items-center gap-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nieuwe Server
</a>
@endsection

@section('content')
{{-- Stats --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Actieve Servers</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $servers->where('status', 'active')->count() }}</p>
            </div>
            <div class="w-12 h-12 bg-brand-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Online</p>
                <p class="text-3xl font-bold text-green-600 mt-1">{{ $servers->where('power_status', 'online')->count() }}</p>
            </div>
            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Open Orders</p>
                <p class="text-3xl font-bold text-yellow-600 mt-1">{{ $recentOrders->where('status', 'pending')->count() }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Totale Servers</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $servers->count() }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
        </div>
    </div>
</div>

{{-- Servers List --}}
<div class="bg-white rounded-xl border border-gray-200 mb-8">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Mijn Servers</h2>
        <a href="{{ route('servers.index') }}" class="text-sm text-brand-600 hover:underline">Alles bekijken</a>
    </div>
    @if($servers->isEmpty())
    <div class="px-6 py-12 text-center">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
        <p class="text-gray-500 mb-4">Je hebt nog geen servers</p>
        <a href="{{ route('servers.create') }}" class="inline-flex items-center gap-2 bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-brand-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Eerste server aanmaken
        </a>
    </div>
    @else
    <div class="divide-y divide-gray-100">
        @foreach($servers as $server)
        <a href="{{ route('servers.show', $server) }}" class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 transition">
            <div class="w-10 h-10 rounded-lg {{ $server->isOnline() ? 'bg-green-100' : 'bg-gray-100' }} flex items-center justify-center">
                <div class="w-3 h-3 rounded-full {{ $server->isOnline() ? 'bg-green-500' : 'bg-gray-400' }}"></div>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ $server->name }}</p>
                <p class="text-xs text-gray-500">{{ $server->ip_address ?? 'Nog geen IP' }} &middot; {{ $server->package->name ?? '-' }}</p>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $server->status_badge }}">
                {{ ucfirst($server->status) }}
            </span>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        @endforeach
    </div>
    @endif
</div>

{{-- Recent Orders --}}
@if($recentOrders->isNotEmpty())
<div class="bg-white rounded-xl border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Recente Bestellingen</h2>
        <a href="{{ route('orders.index') }}" class="text-sm text-brand-600 hover:underline">Alles bekijken</a>
    </div>
    <div class="divide-y divide-gray-100">
        @foreach($recentOrders as $order)
        <div class="flex items-center justify-between px-6 py-4">
            <div>
                <p class="text-sm font-medium text-gray-900">{{ $order->getTypeLabel() }} - {{ $order->package->name ?? '-' }}</p>
                <p class="text-xs text-gray-500">{{ $order->created_at->format('d-m-Y H:i') }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm font-semibold text-gray-900">&euro;{{ number_format($order->total, 2, ',', '.') }}</p>
                <p class="text-xs {{ $order->isPaid() ? 'text-green-600' : 'text-yellow-600' }}">{{ $order->getStatusLabel() }}</p>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection
