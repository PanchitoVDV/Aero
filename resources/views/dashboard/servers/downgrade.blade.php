@extends('layouts.app')
@section('title', 'Resize - ' . $server->name)
@section('header', 'Server Aanpassen')

@section('content')
<div class="max-w-lg mx-auto text-center py-12">
    <p class="text-gray-600 mb-4">Upgrade en downgrade zijn samengevoegd. Gebruik de server resize tool om resources aan te passen.</p>
    <a href="{{ route('servers.upgrade', $server) }}" class="inline-block bg-brand-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-brand-700 transition">
        Naar Server Aanpassen
    </a>
</div>
@endsection
