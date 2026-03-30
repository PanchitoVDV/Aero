@extends('layouts.app')

@section('title', 'Resource Prijzen')
@section('header', 'Resource Prijzen')

@section('content')
<div class="max-w-4xl">
    <div class="bg-white rounded-xl shadow-sm border border-dark-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-dark-200">
            <h2 class="text-lg font-semibold text-dark-900">Per-resource prijzen instellen</h2>
            <p class="text-sm text-dark-500 mt-1">Stel de prijs per eenheid in. De maandprijs van een server wordt berekend op basis van deze tarieven.</p>
        </div>

        <form action="{{ route('admin.pricing.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="divide-y divide-dark-100">
                @foreach($resources as $resource)
                <div class="px-6 py-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-dark-900">{{ $resource->label }}</h3>
                            <p class="text-xs text-dark-400">Type: {{ $resource->resource_type }} | Eenheid: {{ $resource->unit }}</p>
                        </div>
                        @if($resource->resource_type === 'ipv4')
                        <div class="text-right">
                            <span class="text-xs text-dark-400">1x gratis, daarna:</span>
                            <span class="text-sm font-medium text-dark-700">&euro;{{ number_format($resource->price_per_unit, 2, ',', '.') }} per extra IP</span>
                        </div>
                        @elseif($resource->resource_type !== 'base_price')
                        <div class="text-right">
                            <span class="text-xs text-dark-400">Voorbeeld:</span>
                            <span class="text-sm font-medium text-dark-700">{{ $resource->default_value }} {{ $resource->unit }} = &euro;{{ number_format($resource->default_value * $resource->price_per_unit, 2, ',', '.') }}/mnd</span>
                        </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-dark-500 mb-1">Prijs per {{ $resource->unit }}</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-dark-400 text-sm">&euro;</span>
                                <input type="number" name="resources[{{ $resource->id }}][price_per_unit]" value="{{ $resource->price_per_unit }}" step="0.0001" min="0"
                                    class="w-full pl-7 pr-3 py-2 border border-dark-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-dark-500 mb-1">Minimum</label>
                            <input type="number" name="resources[{{ $resource->id }}][min_value]" value="{{ $resource->min_value }}" min="0"
                                class="w-full px-3 py-2 border border-dark-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-dark-500 mb-1">Maximum</label>
                            <input type="number" name="resources[{{ $resource->id }}][max_value]" value="{{ $resource->max_value }}" min="1"
                                class="w-full px-3 py-2 border border-dark-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-dark-500 mb-1">Stap</label>
                            <input type="number" name="resources[{{ $resource->id }}][step]" value="{{ $resource->step }}" min="1"
                                class="w-full px-3 py-2 border border-dark-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-dark-500 mb-1">Standaard</label>
                            <input type="number" name="resources[{{ $resource->id }}][default_value]" value="{{ $resource->default_value }}" min="0"
                                class="w-full px-3 py-2 border border-dark-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="px-6 py-4 bg-dark-50 border-t border-dark-200 flex items-center justify-between">
                <p class="text-xs text-dark-400">Wijzigingen worden direct toegepast op nieuwe bestellingen.</p>
                <button type="submit" class="px-5 py-2.5 bg-brand-600 text-white text-sm font-medium rounded-lg hover:bg-brand-700 transition">
                    Prijzen opslaan
                </button>
            </div>
        </form>
    </div>

    <div class="mt-6 bg-white rounded-xl shadow-sm border border-dark-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-dark-200">
            <h2 class="text-lg font-semibold text-dark-900">Prijsvoorbeeld</h2>
        </div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                @foreach($resources->where('resource_type', '!=', 'base_price') as $r)
                <div class="bg-dark-50 rounded-lg p-3 text-center">
                    <div class="text-xs text-dark-400 mb-1">{{ $r->label }}</div>
                    <div class="text-lg font-bold text-dark-900">{{ $r->default_value }} {{ $r->unit }}</div>
                    @if($r->resource_type === 'ipv4')
                    <div class="text-xs text-dark-500">1x gratis, extra &euro;{{ number_format($r->price_per_unit, 2, ',', '.') }}/IP</div>
                    @else
                    <div class="text-xs text-dark-500">&euro;{{ number_format($r->price_per_unit, 4, ',', '.') }} / {{ $r->unit }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            @php
                $examplePrice = 0;
                $basePrice = $resources->firstWhere('resource_type', 'base_price');
                if ($basePrice) $examplePrice += $basePrice->price_per_unit;
                foreach ($resources->where('resource_type', '!=', 'base_price') as $r) {
                    if ($r->resource_type === 'ipv4') {
                        $examplePrice += max(0, $r->default_value - 1) * $r->price_per_unit;
                    } else {
                        $examplePrice += $r->default_value * $r->price_per_unit;
                    }
                }
            @endphp
            <div class="text-center py-3 bg-brand-50 rounded-lg border border-brand-100">
                <span class="text-sm text-brand-700">Totaal maandprijs:</span>
                <span class="text-2xl font-bold text-brand-600 ml-2">&euro;{{ number_format($examplePrice, 2, ',', '.') }}</span>
                <span class="text-sm text-brand-500">/mnd</span>
            </div>
        </div>
    </div>
</div>
@endsection
