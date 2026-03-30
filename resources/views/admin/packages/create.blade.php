@extends('layouts.app')
@section('title', 'Nieuw Pakket')
@section('header', 'Nieuw Pakket Aanmaken')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.packages.store') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        @csrf

        <div class="grid md:grid-cols-2 gap-5">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Pakketnaam</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="virtfusion_package_id" class="block text-sm font-medium text-gray-700 mb-1.5">VirtFusion Package ID</label>
                <input type="number" name="virtfusion_package_id" id="virtfusion_package_id" value="{{ old('virtfusion_package_id') }}" required
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                @error('virtfusion_package_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">Beschrijving</label>
            <textarea name="description" id="description" rows="3"
                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">{{ old('description') }}</textarea>
        </div>

        <div>
            <label for="category" class="block text-sm font-medium text-gray-700 mb-1.5">Categorie</label>
            <select name="category" id="category" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                <option value="vps">VPS</option>
                <option value="dedicated">Dedicated</option>
                <option value="game">Game</option>
            </select>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label for="cpu_cores" class="block text-sm font-medium text-gray-700 mb-1.5">CPU Cores</label>
                <input type="number" name="cpu_cores" id="cpu_cores" value="{{ old('cpu_cores', 1) }}" min="1" required
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
            </div>
            <div>
                <label for="memory" class="block text-sm font-medium text-gray-700 mb-1.5">RAM (MB)</label>
                <input type="number" name="memory" id="memory" value="{{ old('memory', 1024) }}" min="256" required
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
            </div>
            <div>
                <label for="storage" class="block text-sm font-medium text-gray-700 mb-1.5">Opslag (GB)</label>
                <input type="number" name="storage" id="storage" value="{{ old('storage', 20) }}" min="5" required
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
            </div>
            <div>
                <label for="traffic" class="block text-sm font-medium text-gray-700 mb-1.5">Verkeer (GB)</label>
                <input type="number" name="traffic" id="traffic" value="{{ old('traffic', 2000) }}" min="0" required
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                <p class="text-xs text-gray-400 mt-1">0 = onbeperkt</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label for="price_monthly" class="block text-sm font-medium text-gray-700 mb-1.5">Prijs/maand (&euro;)</label>
                <input type="number" step="0.01" name="price_monthly" id="price_monthly" value="{{ old('price_monthly') }}" required
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
            </div>
            <div>
                <label for="price_quarterly" class="block text-sm font-medium text-gray-700 mb-1.5">Prijs/kwartaal (&euro;)</label>
                <input type="number" step="0.01" name="price_quarterly" id="price_quarterly" value="{{ old('price_quarterly') }}"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
            </div>
            <div>
                <label for="price_yearly" class="block text-sm font-medium text-gray-700 mb-1.5">Prijs/jaar (&euro;)</label>
                <input type="number" step="0.01" name="price_yearly" id="price_yearly" value="{{ old('price_yearly') }}"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
            </div>
            <div>
                <label for="setup_fee" class="block text-sm font-medium text-gray-700 mb-1.5">Setup fee (&euro;)</label>
                <input type="number" step="0.01" name="setup_fee" id="setup_fee" value="{{ old('setup_fee', 0) }}"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
            </div>
        </div>

        <div class="flex items-center gap-6">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                    class="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                <span class="text-sm text-gray-700">Actief</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                    class="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                <span class="text-sm text-gray-700">Uitgelicht</span>
            </label>
        </div>

        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1.5">Volgorde</label>
            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}"
                class="w-32 px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <a href="{{ route('admin.packages') }}" class="text-sm text-gray-600 hover:text-gray-900 px-4 py-2.5">Annuleren</a>
            <button type="submit" class="bg-brand-600 text-white font-semibold px-6 py-2.5 rounded-lg hover:bg-brand-700 transition text-sm">Pakket Aanmaken</button>
        </div>
    </form>
</div>
@endsection
