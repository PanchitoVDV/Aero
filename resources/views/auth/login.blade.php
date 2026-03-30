@extends('layouts.guest')
@section('title', 'Inloggen')

@section('content')
<div class="bg-white rounded-2xl shadow-xl p-8">
    <h2 class="text-2xl font-bold text-gray-900 text-center mb-2">Welkom terug</h2>
    <p class="text-gray-500 text-center mb-8">Log in op je Aero account</p>

    @if (session('status'))
        <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200">
            <p class="text-sm text-green-700">{{ session('status') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">E-mailadres</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Wachtwoord</label>
            <input type="password" name="password" id="password" required
                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                <span class="text-sm text-gray-600">Onthoud mij</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-sm text-brand-600 font-medium hover:underline">Wachtwoord vergeten?</a>
        </div>

        <button type="submit" class="w-full bg-brand-600 text-white font-semibold py-2.5 rounded-lg hover:bg-brand-700 transition">
            Inloggen
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        Nog geen account? <a href="{{ route('register') }}" class="text-brand-600 font-medium hover:underline">Registreer hier</a>
    </p>
</div>
@endsection
