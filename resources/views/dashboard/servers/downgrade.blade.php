@extends('layouts.app')
@section('title', 'Downgrade - ' . $server->name)
@section('header', 'Server Downgraden')

@section('content')
<div class="max-w-4xl">
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <p class="text-sm text-yellow-700">Bij een downgrade worden server resources verlaagd. Zorg ervoor dat je data past binnen het nieuwe pakket.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $server->name }}</h2>
                <p class="text-sm text-gray-500">Huidig pakket: <span class="font-medium text-gray-700">{{ $currentPackage->name }}</span></p>
            </div>
        </div>

        @if($packages->isEmpty())
        <div class="text-center py-8">
            <p class="text-gray-500">Er zijn geen lagere pakketten beschikbaar.</p>
        </div>
        @else
        <form method="POST" action="{{ route('servers.downgrade.process', $server) }}">
            @csrf
            <div class="space-y-4 mb-6">
                @foreach($packages as $package)
                <label class="block cursor-pointer">
                    <input type="radio" name="package_id" value="{{ $package->id }}" class="peer sr-only" required>
                    <div class="border-2 border-gray-200 rounded-xl p-5 peer-checked:border-yellow-500 peer-checked:bg-yellow-50 hover:border-gray-300 transition flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $package->name }}</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ $package->cpu_cores }} vCPU &middot; {{ $package->formatted_memory }} RAM &middot; {{ $package->formatted_storage }} SSD</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-gray-900">&euro;{{ number_format($package->price_monthly, 2, ',', '.') }}<span class="text-sm font-normal text-gray-400">/mnd</span></p>
                            <p class="text-xs text-green-600">Bespaar &euro;{{ number_format($currentPackage->price_monthly - $package->price_monthly, 2, ',', '.') }}/mnd</p>
                        </div>
                    </div>
                </label>
                @endforeach
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ route('servers.show', $server) }}" class="text-sm text-gray-600 hover:text-gray-900 px-4 py-2.5">Annuleren</a>
                <button type="submit" class="bg-yellow-500 text-white font-semibold px-6 py-2.5 rounded-lg hover:bg-yellow-600 transition">Downgraden</button>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection
