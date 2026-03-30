@extends('layouts.app')
@section('title', 'Pakketten Beheer')
@section('header', 'Pakketten Beheer')

@section('header-actions')
<div class="flex items-center gap-3">
    <form method="POST" action="{{ route('admin.packages.sync') }}">
        @csrf
        <button type="submit" class="bg-gray-100 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-200 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Sync VirtFusion
        </button>
    </form>
    <a href="{{ route('admin.packages.create') }}" class="bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-brand-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nieuw Pakket
    </a>
</div>
@endsection

@section('content')
<div class="bg-white rounded-xl border border-gray-200">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Naam</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">VF ID</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Specs</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Prijs/mnd</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Acties</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($packages as $package)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <p class="text-sm font-medium text-gray-900">{{ $package->name }}</p>
                        <p class="text-xs text-gray-500">{{ $package->category }}</p>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $package->virtfusion_package_id }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $package->cpu_cores }}C / {{ $package->formatted_memory }} / {{ $package->formatted_storage }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">&euro;{{ number_format($package->price_monthly, 2, ',', '.') }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $package->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $package->is_active ? 'Actief' : 'Inactief' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.packages.edit', $package) }}" class="text-brand-600 hover:underline text-sm">Bewerken</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
