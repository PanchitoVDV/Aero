@extends('layouts.app')
@section('title', 'Servers')
@section('header', 'Mijn Servers')

@section('header-actions')
<a href="{{ route('servers.create') }}" class="bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-brand-700 transition flex items-center gap-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nieuwe Server
</a>
@endsection

@section('content')
@if($servers->isEmpty())
<div class="bg-white rounded-xl border border-gray-200 px-6 py-16 text-center">
    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
    <h3 class="text-lg font-semibold text-gray-900 mb-2">Geen servers gevonden</h3>
    <p class="text-gray-500 mb-6">Maak je eerste cloud server aan en ga direct aan de slag</p>
    <a href="{{ route('servers.create') }}" class="inline-flex items-center gap-2 bg-brand-600 text-white font-medium px-6 py-2.5 rounded-lg hover:bg-brand-700 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Server Aanmaken
    </a>
</div>
@else
<div class="grid gap-4">
    @foreach($servers as $server)
    <a href="{{ route('servers.show', $server) }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md hover:border-brand-200 transition block">
        <div class="flex items-center gap-5">
            <div class="w-12 h-12 rounded-xl {{ $server->isOnline() ? 'bg-green-100' : 'bg-gray-100' }} flex items-center justify-center flex-shrink-0">
                <div class="w-4 h-4 rounded-full {{ $server->isOnline() ? 'bg-green-500 animate-pulse' : 'bg-gray-400' }}"></div>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-1">
                    <h3 class="text-base font-semibold text-gray-900 truncate">{{ $server->name }}</h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $server->status_badge }}">{{ ucfirst($server->status) }}</span>
                </div>
                <div class="flex items-center gap-4 text-sm text-gray-500">
                    @if($server->ip_address)
                        <span class="font-mono">{{ $server->ip_address }}</span>
                    @else
                        <span class="text-gray-400 italic">Geen IP</span>
                    @endif
                    @if($server->package)
                        <span>&middot;</span>
                        <span>{{ $server->package->name }}</span>
                        <span>&middot;</span>
                        <span>{{ $server->package->formatted_memory }} RAM</span>
                        <span>&middot;</span>
                        <span>{{ $server->package->cpu_cores }} vCPU</span>
                    @elseif($server->custom_ram)
                        <span>&middot;</span>
                        <span>{{ $server->custom_ram }}GB RAM</span>
                        <span>&middot;</span>
                        <span>{{ $server->custom_cpu }} vCPU</span>
                        <span>&middot;</span>
                        <span>{{ $server->custom_storage }}GB SSD</span>
                    @endif
                </div>
            </div>
            @if($server->package && $server->package->price_monthly > 0)
            <div class="text-right flex-shrink-0">
                <p class="text-sm font-semibold text-gray-900">&euro;{{ number_format($server->package->getPriceForCycle($server->billing_cycle ?? 'monthly'), 2, ',', '.') }}</p>
                <p class="text-xs text-gray-500">per {{ ($server->billing_cycle ?? 'monthly') === 'monthly' ? 'maand' : (($server->billing_cycle ?? 'monthly') === 'quarterly' ? 'kwartaal' : 'jaar') }}</p>
            </div>
            @elseif($server->monthly_price)
            <div class="text-right flex-shrink-0">
                <p class="text-sm font-semibold text-gray-900">&euro;{{ number_format($server->monthly_price, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-500">per maand</p>
            </div>
            @endif
            <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </div>
    </a>
    @endforeach
</div>
@endif
@endsection
