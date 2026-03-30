@extends('layouts.app')
@section('title', 'Profiel')
@section('header', 'Mijn Profiel')

@section('content')
<div class="max-w-3xl space-y-6">
    {{-- Personal Info --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Persoonlijke Gegevens</h2>
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Naam</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">E-mail</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="company" class="block text-sm font-medium text-gray-700 mb-1.5">Bedrijfsnaam</label>
                    <input type="text" name="company" id="company" value="{{ old('company', $user->company) }}"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">Telefoonnummer</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                </div>
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1.5">Adres</label>
                    <input type="text" name="address" id="address" value="{{ old('address', $user->address) }}"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                </div>
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1.5">Stad</label>
                    <input type="text" name="city" id="city" value="{{ old('city', $user->city) }}"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                </div>
                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1.5">Postcode</label>
                    <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $user->postal_code) }}"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-brand-600 text-white font-medium px-6 py-2.5 rounded-lg hover:bg-brand-700 transition text-sm">Opslaan</button>
            </div>
        </form>
    </div>

    {{-- Password Change --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Wachtwoord Wijzigen</h2>
        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1.5">Huidig wachtwoord</label>
                <input type="password" name="current_password" id="current_password" required
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Nieuw wachtwoord</label>
                    <input type="password" name="password" id="password" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                    @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Bevestig wachtwoord</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-brand-600 text-white font-medium px-6 py-2.5 rounded-lg hover:bg-brand-700 transition text-sm">Wachtwoord Wijzigen</button>
            </div>
        </form>
    </div>
</div>
@endsection
