@extends('layouts.app')
@section('title', 'Admin Console - ' . $server->name)
@section('header', 'VNC Console - ' . $server->name)

@section('content')
<div class="bg-white rounded-xl border border-gray-200 p-6">
    <div class="bg-gray-900 rounded-lg overflow-hidden" style="min-height: 500px;">
        @if(isset($vncData['data']))
        <iframe
            src="{{ $vncData['data']['url'] ?? '#' }}"
            class="w-full border-0"
            style="height: 600px;"
            allowfullscreen>
        </iframe>
        @else
        <div class="flex items-center justify-center h-96 text-gray-500">
            <div class="text-center">
                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p class="text-gray-400">VNC console wordt geladen...</p>
                <p class="text-gray-500 text-sm mt-2">Zorg dat VNC is ingeschakeld op de server.</p>
            </div>
        </div>
        @endif
    </div>
    <div class="mt-4 flex items-center justify-between">
        <p class="text-sm text-gray-500">Gebruik Ctrl+Alt+Del via de console toolbar</p>
        <a href="{{ route('admin.servers.show', $server) }}" class="text-sm text-brand-600 hover:underline">Terug naar server</a>
    </div>
</div>
@endsection
