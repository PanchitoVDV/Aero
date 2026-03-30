@extends('layouts.app')
@section('title', 'Nieuwe Server')
@section('header', 'Nieuwe Server Aanmaken')

@section('content')
<form method="POST" action="{{ route('servers.store') }}" class="max-w-4xl">
    @csrf

    {{-- Package Selection --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">1. Kies je pakket</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($packages as $package)
            <label class="relative cursor-pointer">
                <input type="radio" name="package_id" value="{{ $package->id }}" class="peer sr-only" {{ old('package_id') == $package->id ? 'checked' : '' }} required>
                <div class="border-2 border-gray-200 rounded-xl p-5 peer-checked:border-brand-500 peer-checked:bg-brand-50 hover:border-gray-300 transition">
                    @if($package->is_featured)
                    <span class="absolute -top-2 right-3 bg-brand-600 text-white text-xs font-semibold px-2 py-0.5 rounded-full">Populair</span>
                    @endif
                    <h3 class="text-base font-semibold text-gray-900">{{ $package->name }}</h3>
                    <p class="text-2xl font-bold text-gray-900 mt-2">&euro;{{ number_format($package->price_monthly, 2, ',', '.') }}<span class="text-sm font-normal text-gray-400">/mnd</span></p>
                    <div class="mt-3 space-y-1.5 text-sm text-gray-600">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $package->cpu_cores }} vCPU {{ $package->cpu_cores > 1 ? 'Cores' : 'Core' }}
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $package->formatted_memory }} RAM
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $package->formatted_storage }} NVMe SSD
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $package->formatted_traffic }} Verkeer
                        </div>
                    </div>
                </div>
            </label>
            @endforeach
        </div>
        @error('package_id')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Server Details --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">2. Server details</h2>
        <div class="grid md:grid-cols-2 gap-5">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Servernaam</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="Mijn Web Server" required
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="hostname" class="block text-sm font-medium text-gray-700 mb-1.5">Hostname</label>
                <input type="text" name="hostname" id="hostname" value="{{ old('hostname') }}" placeholder="server1.cloudito.nl" required
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                @error('hostname')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- OS Selection --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">3. Besturingssysteem</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach([
                ['id' => '1', 'name' => 'Ubuntu 22.04', 'icon' => 'U'],
                ['id' => '2', 'name' => 'Ubuntu 24.04', 'icon' => 'U'],
                ['id' => '3', 'name' => 'Debian 12', 'icon' => 'D'],
                ['id' => '4', 'name' => 'CentOS 9', 'icon' => 'C'],
                ['id' => '5', 'name' => 'AlmaLinux 9', 'icon' => 'A'],
                ['id' => '6', 'name' => 'Rocky Linux 9', 'icon' => 'R'],
                ['id' => '7', 'name' => 'Fedora 39', 'icon' => 'F'],
                ['id' => '8', 'name' => 'Windows Server', 'icon' => 'W'],
            ] as $os)
            <label class="cursor-pointer">
                <input type="radio" name="os_template" value="{{ $os['id'] }}" class="peer sr-only" {{ old('os_template') == $os['id'] ? 'checked' : '' }} required>
                <div class="border-2 border-gray-200 rounded-xl p-4 text-center peer-checked:border-brand-500 peer-checked:bg-brand-50 hover:border-gray-300 transition">
                    <div class="w-10 h-10 mx-auto bg-gray-100 rounded-lg flex items-center justify-center text-lg font-bold text-gray-600 mb-2">{{ $os['icon'] }}</div>
                    <p class="text-xs font-medium text-gray-700">{{ $os['name'] }}</p>
                </div>
            </label>
            @endforeach
        </div>
        @error('os_template')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Billing Cycle --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">4. Factureringsperiode</h2>
        <div class="grid grid-cols-3 gap-4">
            <label class="cursor-pointer">
                <input type="radio" name="billing_cycle" value="monthly" class="peer sr-only" {{ old('billing_cycle', 'monthly') === 'monthly' ? 'checked' : '' }}>
                <div class="border-2 border-gray-200 rounded-xl p-4 text-center peer-checked:border-brand-500 peer-checked:bg-brand-50 hover:border-gray-300 transition">
                    <p class="text-sm font-semibold text-gray-900">Maandelijks</p>
                    <p class="text-xs text-gray-500 mt-1">Flexibel opzegbaar</p>
                </div>
            </label>
            <label class="cursor-pointer">
                <input type="radio" name="billing_cycle" value="quarterly" class="peer sr-only" {{ old('billing_cycle') === 'quarterly' ? 'checked' : '' }}>
                <div class="border-2 border-gray-200 rounded-xl p-4 text-center peer-checked:border-brand-500 peer-checked:bg-brand-50 hover:border-gray-300 transition">
                    <p class="text-sm font-semibold text-gray-900">Per Kwartaal</p>
                    <p class="text-xs text-green-600 mt-1">Bespaar 5%</p>
                </div>
            </label>
            <label class="cursor-pointer">
                <input type="radio" name="billing_cycle" value="yearly" class="peer sr-only" {{ old('billing_cycle') === 'yearly' ? 'checked' : '' }}>
                <div class="border-2 border-gray-200 rounded-xl p-4 text-center peer-checked:border-brand-500 peer-checked:bg-brand-50 hover:border-gray-300 transition">
                    <p class="text-sm font-semibold text-gray-900">Per Jaar</p>
                    <p class="text-xs text-green-600 mt-1">Bespaar 15%</p>
                </div>
            </label>
        </div>
    </div>

    {{-- Submit --}}
    <div class="flex items-center justify-end gap-4">
        <a href="{{ route('servers.index') }}" class="text-sm text-gray-600 hover:text-gray-900 transition">Annuleren</a>
        <button type="submit" class="bg-brand-600 text-white font-semibold px-8 py-3 rounded-lg hover:bg-brand-700 transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            Bestellen &amp; Betalen
        </button>
    </div>
</form>
@endsection
