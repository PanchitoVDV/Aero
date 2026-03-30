@extends('layouts.app')
@section('title', $server->name)
@section('header', $server->name)

@section('header-actions')
<div class="flex items-center gap-2">
    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $server->status_badge }}">{{ ucfirst($server->status) }}</span>
    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $server->power_badge }}">{{ ucfirst($server->power_status) }}</span>
</div>
@endsection

@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    {{-- Left Column --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Server Info --}}
        @php
            $vf = $vfData['data'] ?? $vfData ?? null;

            // IP: network.interfaces[].ipv4[].address
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

            // Specs: settings.resources
            $res = $vf['settings']['resources'] ?? [];
            $liveMem = $res['memory'] ?? null;
            $liveCpu = $res['cpuCores'] ?? $vf['cpu']['cores'] ?? null;

            // Storage: storage[].capacity (primary)
            $liveDisk = null;
            foreach ($vf['storage'] ?? [] as $disk) {
                if (!empty($disk['primary'])) { $liveDisk = $disk['capacity'] ?? null; break; }
            }
            if (!$liveDisk && !empty($vf['storage'][0]['capacity'])) {
                $liveDisk = $vf['storage'][0]['capacity'];
            }

            // Traffic: traffic.public.currentPeriod.limit
            $liveTraffic = $vf['traffic']['public']['currentPeriod']['limit'] ?? null;

            // OS: os.templateName
            $osName = $server->os_template ?? $vf['os']['templateName'] ?? '-';

            // Hostname
            $liveHostname = $server->hostname;
            if (!$liveHostname && $vf) {
                $hRaw = $vf['hostname'] ?? null;
                $liveHostname = is_string($hRaw) ? $hRaw : null;
            }
            $liveHostname = $liveHostname ?: '-';
        @endphp

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Server Informatie</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">IP Adres</p>
                    <p class="text-sm font-mono font-medium text-gray-900 mt-0.5">{{ $liveIp ?? 'Wordt toegewezen...' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Hostname</p>
                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $liveHostname }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Pakket</p>
                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $server->package->name ?? 'Custom configuratie' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">OS</p>
                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $osName }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">CPU</p>
                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $liveCpu ?? $server->custom_cpu ?? $server->package->cpu_cores ?? '-' }} vCPU</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">RAM</p>
                    @php
                        $memVal = $liveMem;
                        if (!$memVal && $server->custom_ram) {
                            $memVal = $server->custom_ram * 1024;
                        }
                        if (!$memVal && $server->package) {
                            $memVal = $server->package->memory;
                        }
                        $memDisplay = '-';
                        if ($memVal && is_numeric($memVal)) {
                            $memDisplay = $memVal >= 1024 ? round($memVal / 1024, 1) . ' GB' : $memVal . ' MB';
                        }
                    @endphp
                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $memDisplay }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Opslag</p>
                    @php
                        $diskVal = $liveDisk ?? $server->custom_storage ?? ($server->package ? $server->package->storage : null);
                        $diskDisplay = is_numeric($diskVal) ? $diskVal . ' GB NVMe SSD' : '-';
                    @endphp
                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $diskDisplay }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Verkeer</p>
                    @php
                        $trafficVal = $liveTraffic ?? ($server->package ? $server->package->traffic : null);
                        $trafficDisplay = '-';
                        if ($trafficVal !== null && is_numeric($trafficVal)) {
                            if ($trafficVal == 0) $trafficDisplay = 'Onbeperkt';
                            elseif ($trafficVal >= 1000) $trafficDisplay = round($trafficVal / 1000, 1) . ' TB';
                            else $trafficDisplay = $trafficVal . ' GB';
                        }
                    @endphp
                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $trafficDisplay }}</p>
                </div>
            </div>
        </div>

        {{-- Power Controls --}}
        @if($server->isActive())
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Power Beheer</h2>
            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route('servers.power', $server) }}">
                    @csrf
                    <input type="hidden" name="action" value="boot">
                    <button type="submit" class="inline-flex items-center gap-2 bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-green-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                        Start
                    </button>
                </form>
                <form method="POST" action="{{ route('servers.power', $server) }}">
                    @csrf
                    <input type="hidden" name="action" value="restart">
                    <button type="submit" class="inline-flex items-center gap-2 bg-yellow-500 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-yellow-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Herstart
                    </button>
                </form>
                <form method="POST" action="{{ route('servers.power', $server) }}">
                    @csrf
                    <input type="hidden" name="action" value="shutdown">
                    <button type="submit" class="inline-flex items-center gap-2 bg-gray-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                        Afsluiten
                    </button>
                </form>
                <form method="POST" action="{{ route('servers.power', $server) }}" onsubmit="return confirm('Weet je zeker dat je de server wilt forceren uit te schakelen?')">
                    @csrf
                    <input type="hidden" name="action" value="poweroff">
                    <button type="submit" class="inline-flex items-center gap-2 bg-red-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        Force Stop
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Rename --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Servernaam Wijzigen</h2>
            <form method="POST" action="{{ route('servers.rename', $server) }}" class="flex gap-3">
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
            @if($activities->isEmpty())
                <p class="text-sm text-gray-500">Geen recente activiteiten.</p>
            @else
            <div class="space-y-3">
                @foreach($activities as $log)
                <div class="flex items-start gap-3 text-sm">
                    <div class="w-2 h-2 mt-1.5 rounded-full bg-brand-400 flex-shrink-0"></div>
                    <div>
                        <p class="text-gray-700">{{ $log->description }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
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
                @if($server->isActive())
                <a href="{{ route('servers.console', $server) }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition text-sm font-medium text-gray-700">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    VNC Console
                </a>
                <form method="POST" action="{{ route('servers.reset-password', $server) }}">
                    @csrf
                    <button type="submit" onclick="return confirm('Wachtwoord resetten? Je ontvangt een email.')" class="w-full flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition text-sm font-medium text-gray-700 text-left">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        Wachtwoord Resetten
                    </button>
                </form>
                @endif
                <a href="{{ route('servers.upgrade', $server) }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition text-sm font-medium text-gray-700">
                    <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Resources Aanpassen
                </a>
            </div>
        </div>

        {{-- Billing Info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Facturering</h2>
            <div class="space-y-3">
                @if($server->package)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Pakket</span>
                    <span class="font-medium text-gray-900">{{ $server->package->name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Prijs</span>
                    <span class="font-medium text-gray-900">&euro;{{ number_format($server->package->getPriceForCycle($server->billing_cycle), 2, ',', '.') }}</span>
                </div>
                @else
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Configuratie</span>
                    <span class="font-medium text-gray-900">{{ $server->custom_ram ?? '-' }}GB / {{ $server->custom_cpu ?? '-' }} vCPU / {{ $server->custom_storage ?? '-' }}GB SSD</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Prijs</span>
                    <span class="font-medium text-gray-900">&euro;{{ number_format($server->monthly_price ?? 0, 2, ',', '.') }}/mnd</span>
                </div>
                @endif
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Cyclus</span>
                    <span class="font-medium text-gray-900">{{ ucfirst($server->billing_cycle === 'monthly' ? 'Maandelijks' : ($server->billing_cycle === 'quarterly' ? 'Per kwartaal' : 'Per jaar')) }}</span>
                </div>
                @if($server->next_due_date)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Volgende betaling</span>
                    <span class="font-medium text-gray-900">{{ $server->next_due_date->format('d-m-Y') }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Danger Zone --}}
        <div class="bg-white rounded-xl border border-red-200 p-6">
            <h2 class="text-lg font-semibold text-red-600 mb-4">Gevarenzone</h2>
            <p class="text-sm text-gray-600 mb-4">Het verwijderen van een server is permanent en kan niet ongedaan worden gemaakt.</p>
            <form method="POST" action="{{ route('servers.destroy', $server) }}" onsubmit="return document.getElementById('confirm_delete').value === 'DELETE'">
                @csrf
                @method('DELETE')
                <input type="text" id="confirm_delete" name="confirm" placeholder="Typ DELETE om te bevestigen"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition text-sm mb-3">
                <button type="submit" class="w-full bg-red-600 text-white text-sm font-medium py-2.5 rounded-lg hover:bg-red-700 transition">
                    Server Verwijderen
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
