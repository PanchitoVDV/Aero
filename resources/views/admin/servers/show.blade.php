@extends('layouts.app')
@section('title', 'Admin - ' . $server->name)
@section('header', $server->name)

@section('header-actions')
<div class="flex items-center gap-2">
    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $server->status_badge }}">{{ ucfirst($server->status) }}</span>
    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $server->power_badge }}">{{ ucfirst($server->power_status) }}</span>
    <a href="{{ route('admin.servers') }}" class="ml-2 text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Terug
    </a>
</div>
@endsection

@section('content')
@php
    $vf = $vfData['data'] ?? $vfData ?? null;

    // IP
    $liveIp = $server->ip_address;
    if ($vf && !$liveIp) {
        foreach ($vf['network']['interfaces'] ?? [] as $iface) {
            foreach ($iface['ipv4'] ?? [] as $ipEntry) {
                $ip = $ipEntry['address'] ?? null;
                if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $liveIp = $ip;
                    break 2;
                }
            }
        }
    }

    // Specs
    $res = $vf['settings']['resources'] ?? [];
    $liveMem = $res['memory'] ?? null;
    $liveCpu = $res['cpuCores'] ?? $vf['cpu']['cores'] ?? null;

    $liveDisk = null;
    foreach ($vf['storage'] ?? [] as $disk) {
        if (!empty($disk['primary'])) { $liveDisk = $disk['capacity'] ?? null; break; }
    }
    if (!$liveDisk && !empty($vf['storage'][0]['capacity'])) {
        $liveDisk = $vf['storage'][0]['capacity'];
    }

    $liveTraffic = $vf['traffic']['public']['currentPeriod']['limit'] ?? null;
    $osName = $server->os_template ?? $vf['os']['templateName'] ?? '-';
    $liveHostname = $server->hostname ?? (is_string($vf['hostname'] ?? null) ? $vf['hostname'] : null) ?? '-';

    $hypervisor = $vf['hypervisor'] ?? null;
    $vncInfo = $vf['vnc'] ?? null;
@endphp

<div class="grid lg:grid-cols-3 gap-6">
    {{-- Left Column --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Server Info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Server Informatie</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">IP Adres</p>
                    <p class="text-sm font-mono font-semibold text-gray-900 mt-1">{{ $liveIp ?? 'Niet toegewezen' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Hostname</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $liveHostname }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">OS</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $osName }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">CPU</p>
                    <p class="text-sm font-semibold text-gray-900 mt-1">{{ $liveCpu ?? $server->package->cpu_cores ?? '-' }} vCPU</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">RAM</p>
                    @php
                        $memVal = $liveMem ?? ($server->package ? $server->package->memory : null);
                        $memDisplay = $memVal && is_numeric($memVal) ? ($memVal >= 1024 ? round($memVal / 1024, 1) . ' GB' : $memVal . ' MB') : '-';
                    @endphp
                    <p class="text-sm font-semibold text-gray-900 mt-1">{{ $memDisplay }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Opslag</p>
                    @php $diskDisplay = ($liveDisk ?? ($server->package ? $server->package->storage : null)); @endphp
                    <p class="text-sm font-semibold text-gray-900 mt-1">{{ is_numeric($diskDisplay) ? $diskDisplay . ' GB' : '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Verkeer</p>
                    @php
                        $tv = $liveTraffic ?? ($server->package ? $server->package->traffic : null);
                        $td = '-';
                        if ($tv !== null && is_numeric($tv)) { $td = $tv == 0 ? 'Onbeperkt' : ($tv >= 1000 ? round($tv/1000,1) . ' TB' : $tv . ' GB'); }
                    @endphp
                    <p class="text-sm font-semibold text-gray-900 mt-1">{{ $td }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Pakket</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $server->package->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">VF Server ID</p>
                    <p class="text-sm font-mono font-medium text-gray-900 mt-1">{{ $server->virtfusion_server_id ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Owner Info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Eigenaar</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Naam</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $server->user->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Email</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $server->user->email ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">VF User ID</p>
                    <p class="text-sm font-mono font-medium text-gray-900 mt-1">{{ $server->user->virtfusion_user_id ?? '-' }}</p>
                </div>
            </div>
        </div>

        @if($hypervisor)
        {{-- Hypervisor Info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Hypervisor</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Node</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $hypervisor['name'] ?? $hypervisor['group']['name'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">IP</p>
                    <p class="text-sm font-mono font-medium text-gray-900 mt-1">{{ $hypervisor['ip'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Groep</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $hypervisor['group']['name'] ?? '-' }}</p>
                </div>
                @if(isset($hypervisor['resources']))
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">RAM gebruik</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $hypervisor['resources']['memory']['percent'] ?? '-' }}%</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">CPU gebruik</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $hypervisor['resources']['cpuCores']['percent'] ?? '-' }}%</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Opslag gebruik</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $hypervisor['resources']['localStorage']['percent'] ?? '-' }}%</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Power Controls --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Power Beheer</h2>
            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route('admin.servers.power', $server) }}">
                    @csrf
                    <input type="hidden" name="action" value="boot">
                    <button type="submit" class="inline-flex items-center gap-2 bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-green-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                        Start
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.servers.power', $server) }}">
                    @csrf
                    <input type="hidden" name="action" value="restart">
                    <button type="submit" class="inline-flex items-center gap-2 bg-yellow-500 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-yellow-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Herstart
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.servers.power', $server) }}">
                    @csrf
                    <input type="hidden" name="action" value="shutdown">
                    <button type="submit" class="inline-flex items-center gap-2 bg-gray-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                        Afsluiten
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.servers.power', $server) }}" onsubmit="return confirm('Weet je zeker dat je de server wilt forceren uitschakelen?')">
                    @csrf
                    <input type="hidden" name="action" value="poweroff">
                    <button type="submit" class="inline-flex items-center gap-2 bg-red-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        Force Stop
                    </button>
                </form>
            </div>
        </div>

        {{-- Rename --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Servernaam Wijzigen</h2>
            <form method="POST" action="{{ route('admin.servers.rename', $server) }}" class="flex gap-3">
                @csrf
                @method('PUT')
                <input type="text" name="name" value="{{ $server->name }}" required
                    class="flex-1 px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                <button type="submit" class="bg-brand-600 text-white text-sm font-medium px-4 py-2.5 rounded-lg hover:bg-brand-700 transition">Opslaan</button>
            </form>
        </div>

        {{-- Activity Log --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Activiteiten</h2>
            @if($server->activityLogs->isEmpty())
                <p class="text-sm text-gray-500">Geen recente activiteiten.</p>
            @else
            <div class="space-y-3">
                @foreach($server->activityLogs as $log)
                <div class="flex items-start gap-3 text-sm">
                    <div class="w-2 h-2 mt-1.5 rounded-full bg-brand-400 flex-shrink-0"></div>
                    <div class="flex-1">
                        <p class="text-gray-700">{{ $log->description }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $log->created_at->diffForHumans() }} &middot; {{ $log->ip_address }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Right Column --}}
    <div class="space-y-6">
        {{-- Quick Actions --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Acties</h2>
            <div class="space-y-2">
                <a href="{{ route('admin.servers.console', $server) }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition text-sm font-medium text-gray-700">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    VNC Console
                </a>
                <form method="POST" action="{{ route('admin.servers.reset-password', $server) }}">
                    @csrf
                    <button type="submit" onclick="return confirm('Server wachtwoord resetten?')" class="w-full flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition text-sm font-medium text-gray-700 text-left">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        Server Wachtwoord Resetten
                    </button>
                </form>

                @if($server->status !== 'suspended')
                <form method="POST" action="{{ route('admin.servers.suspend', $server) }}">
                    @csrf
                    <button type="submit" onclick="return confirm('Server suspenderen?')" class="w-full flex items-center gap-3 p-3 rounded-lg hover:bg-red-50 transition text-sm font-medium text-red-600 text-left">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        Suspenderen
                    </button>
                </form>
                @else
                <form method="POST" action="{{ route('admin.servers.unsuspend', $server) }}">
                    @csrf
                    <button type="submit" onclick="return confirm('Server unsuspenderen?')" class="w-full flex items-center gap-3 p-3 rounded-lg hover:bg-green-50 transition text-sm font-medium text-green-600 text-left">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Unsuspenderen
                    </button>
                </form>
                @endif
            </div>
        </div>

        @if($vncInfo)
        {{-- VNC Info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">VNC</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">IP</span>
                    <span class="font-mono text-gray-900">{{ $vncInfo['ip'] ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Poort</span>
                    <span class="font-mono text-gray-900">{{ $vncInfo['port'] ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>
                    <span class="font-medium {{ ($vncInfo['enabled'] ?? false) ? 'text-green-600' : 'text-red-600' }}">
                        {{ ($vncInfo['enabled'] ?? false) ? 'Actief' : 'Inactief' }}
                    </span>
                </div>
            </div>
        </div>
        @endif

        {{-- Timestamps --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Datums</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Aangemaakt</span>
                    <span class="text-gray-900">{{ $server->created_at->format('d-m-Y H:i') }}</span>
                </div>
                @if($server->next_due_date)
                <div class="flex justify-between">
                    <span class="text-gray-500">Volgende betaling</span>
                    <span class="text-gray-900">{{ $server->next_due_date->format('d-m-Y') }}</span>
                </div>
                @endif
                @if($server->suspended_at)
                <div class="flex justify-between">
                    <span class="text-gray-500">Gesuspend op</span>
                    <span class="text-red-600">{{ $server->suspended_at->format('d-m-Y H:i') }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Danger Zone --}}
        <div class="bg-white rounded-xl border border-red-200 p-6">
            <h2 class="text-lg font-semibold text-red-600 mb-4">Gevarenzone</h2>
            <p class="text-sm text-gray-600 mb-4">Het verwijderen van een server is permanent. De server wordt ook in VirtFusion verwijderd.</p>
            <form method="POST" action="{{ route('admin.servers.destroy', $server) }}" onsubmit="return document.getElementById('admin_confirm_delete').value === 'DELETE'">
                @csrf
                @method('DELETE')
                <input type="text" id="admin_confirm_delete" name="confirm" placeholder="Typ DELETE om te bevestigen"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition text-sm mb-3">
                <button type="submit" class="w-full bg-red-600 text-white text-sm font-medium py-2.5 rounded-lg hover:bg-red-700 transition">
                    Server Verwijderen
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
