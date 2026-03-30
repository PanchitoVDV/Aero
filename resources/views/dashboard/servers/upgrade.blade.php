@extends('layouts.app')
@section('title', 'Resize - ' . $server->name)
@section('header', 'Server Aanpassen')

@section('content')
<div class="max-w-4xl">
    {{-- Current Specs --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-brand-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $server->name }}</h2>
                <p class="text-sm text-gray-500">Pas de resources aan. Upgrade betaal je direct, downgrade gaat in op de volgende factureringsperiode.</p>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <div class="text-xs text-gray-400">RAM</div>
                <div class="text-lg font-bold text-gray-900">{{ $currentSpecs['ram'] }} GB</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <div class="text-xs text-gray-400">CPU</div>
                <div class="text-lg font-bold text-gray-900">{{ $currentSpecs['cpu'] }} vCPU</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <div class="text-xs text-gray-400">Opslag</div>
                <div class="text-lg font-bold text-gray-900">{{ $currentSpecs['storage'] }} GB</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <div class="text-xs text-gray-400">IPv4</div>
                <div class="text-lg font-bold text-gray-900">{{ $currentSpecs['ipv4'] }}</div>
            </div>
        </div>
        <div class="mt-3 text-center">
            <span class="text-sm text-gray-500">Huidige maandprijs:</span>
            <span class="text-sm font-bold text-gray-900">&euro;{{ number_format($currentMonthly, 2, ',', '.') }}/mnd</span>
        </div>
    </div>

    {{-- Resize Sliders --}}
    <form method="POST" action="{{ route('servers.upgrade.process', $server) }}" id="resizeForm">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Nieuwe configuratie</h2>
            <p class="text-sm text-gray-500 mb-6">Verschuif de sliders naar de gewenste resources.</p>

            <div class="space-y-6">
                @foreach($pricing->where('resource_type', '!=', 'base_price') as $resource)
                @php
                    $currentVal = match($resource->resource_type) {
                        'ram_gb' => $currentSpecs['ram'],
                        'cpu_core' => $currentSpecs['cpu'],
                        'storage_gb' => $currentSpecs['storage'],
                        'ipv4' => $currentSpecs['ipv4'],
                        default => $resource->default_value,
                    };
                @endphp
                <div class="slider-group" data-resource="{{ $resource->resource_type }}" data-price="{{ $resource->price_per_unit }}" data-current="{{ $currentVal }}">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-gray-700">{{ $resource->label }}</label>
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-bold text-gray-900 slider-value">{{ $currentVal }}</span>
                            <span class="text-sm text-gray-500">{{ $resource->unit }}</span>
                            <span class="text-xs text-gray-400 ml-2 slider-diff"></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-xs text-gray-400 w-8 text-right">{{ $resource->min_value }}</span>
                        <input type="range"
                            name="{{ $resource->resource_type }}"
                            min="{{ $resource->min_value }}"
                            max="{{ $resource->max_value }}"
                            step="{{ $resource->step }}"
                            value="{{ old($resource->resource_type, $currentVal) }}"
                            class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-brand-600">
                        <span class="text-xs text-gray-400 w-8">{{ $resource->max_value }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Price Comparison --}}
        <div class="rounded-xl border-2 p-6 mb-6 transition-colors" id="priceBox">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Nieuwe maandprijs</h3>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span class="text-3xl font-bold" id="newPrice">&euro;0,00</span>
                        <span class="text-gray-400">/mnd</span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Verschil</div>
                    <div class="text-xl font-bold" id="priceDiff">&euro;0,00</div>
                    <div class="text-xs" id="diffLabel"></div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('servers.show', $server) }}" class="text-sm text-gray-600 hover:text-gray-900 transition">Annuleren</a>
            <button type="submit" class="bg-brand-600 text-white font-semibold px-8 py-3 rounded-lg hover:bg-brand-700 transition flex items-center gap-2" id="submitBtn">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span id="submitText">Configuratie aanpassen</span>
            </button>
        </div>
    </form>
</div>

<script>
(function() {
    const basePrice = {{ $basePrice }};
    const currentMonthly = {{ $currentMonthly }};
    const sliderGroups = document.querySelectorAll('.slider-group');

    function formatEur(value) {
        return '\u20AC' + value.toFixed(2).replace('.', ',');
    }

    function calculate() {
        let monthly = basePrice;

        sliderGroups.forEach(group => {
            const slider = group.querySelector('input[type="range"]');
            const price = parseFloat(group.dataset.price);
            const resource = group.dataset.resource;
            const currentVal = parseInt(group.dataset.current);
            const val = parseInt(slider.value);
            const billableUnits = (resource === 'ipv4') ? Math.max(0, val - 1) : val;
            monthly += billableUnits * price;

            group.querySelector('.slider-value').textContent = val;

            const diff = val - currentVal;
            const diffEl = group.querySelector('.slider-diff');
            if (diff > 0) {
                diffEl.textContent = '+' + diff;
                diffEl.className = 'text-xs font-medium ml-2 slider-diff text-green-600';
            } else if (diff < 0) {
                diffEl.textContent = diff.toString();
                diffEl.className = 'text-xs font-medium ml-2 slider-diff text-red-600';
            } else {
                diffEl.textContent = '';
                diffEl.className = 'text-xs ml-2 slider-diff';
            }
        });

        const diff = monthly - currentMonthly;
        const box = document.getElementById('priceBox');
        const diffEl = document.getElementById('priceDiff');
        const diffLabel = document.getElementById('diffLabel');
        const submitText = document.getElementById('submitText');
        const submitBtn = document.getElementById('submitBtn');

        document.getElementById('newPrice').textContent = formatEur(monthly);

        if (diff > 0.01) {
            diffEl.textContent = '+' + formatEur(diff);
            diffEl.className = 'text-xl font-bold text-green-600';
            diffLabel.textContent = 'Upgrade - betaal direct';
            diffLabel.className = 'text-xs text-green-600';
            box.className = 'rounded-xl border-2 border-green-200 bg-green-50 p-6 mb-6 transition-colors';
            submitText.textContent = 'Upgraden & Betalen';
            submitBtn.className = 'bg-brand-600 text-white font-semibold px-8 py-3 rounded-lg hover:bg-brand-700 transition flex items-center gap-2';
            submitBtn.disabled = false;
        } else if (diff < -0.01) {
            diffEl.textContent = formatEur(diff);
            diffEl.className = 'text-xl font-bold text-yellow-600';
            diffLabel.textContent = 'Downgrade - gaat in op volgende periode';
            diffLabel.className = 'text-xs text-yellow-600';
            box.className = 'rounded-xl border-2 border-yellow-200 bg-yellow-50 p-6 mb-6 transition-colors';
            submitText.textContent = 'Downgraden';
            submitBtn.className = 'bg-yellow-500 text-white font-semibold px-8 py-3 rounded-lg hover:bg-yellow-600 transition flex items-center gap-2';
            submitBtn.disabled = false;
        } else {
            diffEl.textContent = formatEur(0);
            diffEl.className = 'text-xl font-bold text-gray-400';
            diffLabel.textContent = 'Geen wijziging';
            diffLabel.className = 'text-xs text-gray-400';
            box.className = 'rounded-xl border-2 border-gray-200 p-6 mb-6 transition-colors';
            submitText.textContent = 'Geen wijziging';
            submitBtn.className = 'bg-gray-300 text-gray-500 font-semibold px-8 py-3 rounded-lg cursor-not-allowed flex items-center gap-2';
            submitBtn.disabled = true;
        }
    }

    sliderGroups.forEach(group => {
        group.querySelector('input[type="range"]').addEventListener('input', calculate);
    });

    calculate();
})();
</script>

<style>
    input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; width: 20px; height: 20px; background: #2563eb; border-radius: 50%; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
    input[type="range"]::-moz-range-thumb { width: 20px; height: 20px; background: #2563eb; border-radius: 50%; cursor: pointer; border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
</style>
@endsection
