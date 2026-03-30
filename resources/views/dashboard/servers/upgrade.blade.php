@extends('layouts.app')
@section('title', 'Upgrade - ' . $server->name)
@section('header', 'Server Upgraden')

@section('content')
<div class="max-w-4xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 bg-brand-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $server->name }}</h2>
                <p class="text-sm text-gray-500">Huidig pakket: <span class="font-medium text-gray-700">{{ $currentPackage->name }}</span> - &euro;{{ number_format($currentPackage->price_monthly, 2, ',', '.') }}/mnd</p>
            </div>
        </div>

        @if($packages->isEmpty())
        <div class="text-center py-8">
            <p class="text-gray-500">Er zijn geen hogere pakketten beschikbaar.</p>
            <a href="{{ route('servers.show', $server) }}" class="mt-4 inline-block text-brand-600 hover:underline text-sm">Terug naar server</a>
        </div>
        @else
        <form method="POST" action="{{ route('servers.upgrade.process', $server) }}">
            @csrf
            <div class="space-y-4 mb-6">
                @foreach($packages as $package)
                <label class="block cursor-pointer">
                    <input type="radio" name="package_id" value="{{ $package->id }}" class="peer sr-only" required>
                    <div class="border-2 border-gray-200 rounded-xl p-5 peer-checked:border-brand-500 peer-checked:bg-brand-50 hover:border-gray-300 transition flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $package->name }}</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ $package->cpu_cores }} vCPU &middot; {{ $package->formatted_memory }} RAM &middot; {{ $package->formatted_storage }} SSD &middot; {{ $package->formatted_traffic }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-gray-900">&euro;{{ number_format($package->price_monthly, 2, ',', '.') }}<span class="text-sm font-normal text-gray-400">/mnd</span></p>
                            <p class="text-xs text-green-600">+&euro;{{ number_format($package->price_monthly - $currentPackage->price_monthly, 2, ',', '.') }}/mnd</p>
                        </div>
                    </div>
                </label>
                @endforeach
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ route('servers.show', $server) }}" class="text-sm text-gray-600 hover:text-gray-900 px-4 py-2.5">Annuleren</a>
                <button type="submit" class="bg-brand-600 text-white font-semibold px-6 py-2.5 rounded-lg hover:bg-brand-700 transition">Upgraden &amp; Betalen</button>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection
