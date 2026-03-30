@extends('layouts.guest')
@section('title', 'Wachtwoord Resetten')

@section('content')
<div class="bg-white rounded-2xl shadow-xl p-8">
    <h2 class="text-2xl font-bold text-gray-900 text-center mb-2">Nieuw wachtwoord</h2>
    <p class="text-gray-500 text-center mb-8">Kies een nieuw wachtwoord voor je account</p>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">E-mailadres</label>
            <input type="email" name="email" id="email" value="{{ old('email', $email) }}" required autofocus
                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Nieuw wachtwoord</label>
            <input type="password" name="password" id="password" required
                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Wachtwoord bevestigen</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required
                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
        </div>

        <button type="submit" class="w-full bg-brand-600 text-white font-semibold py-2.5 rounded-lg hover:bg-brand-700 transition">
            Wachtwoord Opslaan
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        <a href="{{ route('login') }}" class="text-brand-600 font-medium hover:underline">Terug naar inloggen</a>
    </p>
</div>
@endsection
