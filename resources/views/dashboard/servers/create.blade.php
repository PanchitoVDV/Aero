@extends('layouts.app')
@section('title', 'Nieuwe Server')
@section('header', 'Nieuwe Server Aanmaken')

@section('content')
<form method="POST" action="{{ route('servers.store') }}" class="max-w-4xl" id="serverForm">
    @csrf

    {{-- Server Configurator --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">1. Configureer je server</h2>
        <p class="text-sm text-gray-500 mb-6">Pas de resources aan met de sliders. De prijs wordt live berekend.</p>

        <div class="space-y-6">
            @foreach($pricing->where('resource_type', '!=', 'base_price') as $resource)
            <div class="slider-group" data-resource="{{ $resource->resource_type }}" data-price="{{ $resource->price_per_unit }}">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-medium text-gray-700">{{ $resource->label }}</label>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-bold text-gray-900 slider-value">{{ $resource->default_value }}</span>
                        <span class="text-sm text-gray-500">{{ $resource->unit }}</span>
                        <span class="text-xs text-gray-400 ml-2 slider-cost">&euro;{{ number_format($resource->default_value * $resource->price_per_unit, 2, ',', '.') }}/mnd</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-xs text-gray-400 w-8 text-right">{{ $resource->min_value }}</span>
                    <input type="range"
                        name="{{ $resource->resource_type }}"
                        min="{{ $resource->min_value }}"
                        max="{{ $resource->max_value }}"
                        step="{{ $resource->step }}"
                        value="{{ old($resource->resource_type, $resource->default_value) }}"
                        class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-brand-600">
                    <span class="text-xs text-gray-400 w-8">{{ $resource->max_value }}</span>
                </div>
                <div class="flex justify-between mt-1">
                    <span class="text-xs text-gray-400">&euro;{{ number_format($resource->price_per_unit, 4, ',', '.') }} per {{ $resource->unit }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Price Summary --}}
    <div class="bg-gradient-to-r from-brand-600 to-brand-700 rounded-xl p-6 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-medium text-brand-100">Geschatte maandprijs</h3>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl font-bold" id="totalPrice">&euro;0,00</span>
                    <span class="text-brand-200">/mnd</span>
                </div>
            </div>
            <div class="text-right space-y-1">
                <div class="text-xs text-brand-200">Kwartaal (5% korting): <span id="quarterlyPrice" class="font-semibold text-white">&euro;0,00</span></div>
                <div class="text-xs text-brand-200">Jaarlijks (15% korting): <span id="yearlyPrice" class="font-semibold text-white">&euro;0,00</span></div>
            </div>
        </div>
        @php
            $basePrice = $pricing->firstWhere('resource_type', 'base_price');
        @endphp
        @if($basePrice && $basePrice->price_per_unit > 0)
        <div class="mt-3 pt-3 border-t border-brand-500 text-xs text-brand-200">
            Inclusief basisprijs van &euro;{{ number_format($basePrice->price_per_unit, 2, ',', '.') }}/mnd
        </div>
        @endif
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
                    <p class="text-sm font-bold text-gray-900 mt-2 cycle-price" data-cycle="monthly"></p>
                </div>
            </label>
            <label class="cursor-pointer">
                <input type="radio" name="billing_cycle" value="quarterly" class="peer sr-only" {{ old('billing_cycle') === 'quarterly' ? 'checked' : '' }}>
                <div class="border-2 border-gray-200 rounded-xl p-4 text-center peer-checked:border-brand-500 peer-checked:bg-brand-50 hover:border-gray-300 transition">
                    <p class="text-sm font-semibold text-gray-900">Per Kwartaal</p>
                    <p class="text-xs text-green-600 mt-1">Bespaar 5%</p>
                    <p class="text-sm font-bold text-gray-900 mt-2 cycle-price" data-cycle="quarterly"></p>
                </div>
            </label>
            <label class="cursor-pointer">
                <input type="radio" name="billing_cycle" value="yearly" class="peer sr-only" {{ old('billing_cycle') === 'yearly' ? 'checked' : '' }}>
                <div class="border-2 border-gray-200 rounded-xl p-4 text-center peer-checked:border-brand-500 peer-checked:bg-brand-50 hover:border-gray-300 transition">
                    <p class="text-sm font-semibold text-gray-900">Per Jaar</p>
                    <p class="text-xs text-green-600 mt-1">Bespaar 15%</p>
                    <p class="text-sm font-bold text-gray-900 mt-2 cycle-price" data-cycle="yearly"></p>
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

<script>
(function() {
    const basePrice = {{ $basePrice ? $basePrice->price_per_unit : 0 }};
    const sliderGroups = document.querySelectorAll('.slider-group');

    function formatEur(value) {
        return '\u20AC' + value.toFixed(2).replace('.', ',');
    }

    function calculatePrice() {
        let monthly = basePrice;

        sliderGroups.forEach(group => {
            const slider = group.querySelector('input[type="range"]');
            const price = parseFloat(group.dataset.price);
            const val = parseInt(slider.value);
            const cost = val * price;
            monthly += cost;

            group.querySelector('.slider-value').textContent = val;
            group.querySelector('.slider-cost').textContent = formatEur(cost) + '/mnd';
        });

        const quarterly = monthly * 3 * 0.95;
        const yearly = monthly * 12 * 0.85;

        document.getElementById('totalPrice').textContent = formatEur(monthly);
        document.getElementById('quarterlyPrice').textContent = formatEur(quarterly);
        document.getElementById('yearlyPrice').textContent = formatEur(yearly);

        document.querySelectorAll('.cycle-price').forEach(el => {
            switch(el.dataset.cycle) {
                case 'monthly': el.textContent = formatEur(monthly); break;
                case 'quarterly': el.textContent = formatEur(quarterly) + '/kw'; break;
                case 'yearly': el.textContent = formatEur(yearly) + '/jr'; break;
            }
        });
    }

    sliderGroups.forEach(group => {
        group.querySelector('input[type="range"]').addEventListener('input', calculatePrice);
    });

    calculatePrice();
})();
</script>

<style>
    input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 20px;
        height: 20px;
        background: #2563eb;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    input[type="range"]::-moz-range-thumb {
        width: 20px;
        height: 20px;
        background: #2563eb;
        border-radius: 50%;
        cursor: pointer;
        border: none;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
</style>
@endsection
