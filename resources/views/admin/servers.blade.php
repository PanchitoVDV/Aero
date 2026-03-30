@extends('layouts.app')
@section('title', 'Servers Beheer')
@section('header', 'Alle Servers')

@section('content')
<div class="bg-white rounded-xl border border-gray-200">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Server</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Klant</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pakket</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">IP</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Power</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">VF ID</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($servers as $server)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <a href="{{ route('servers.show', $server) }}" class="text-sm font-medium text-brand-600 hover:underline">{{ $server->name }}</a>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $server->user->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $server->package->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ $server->ip_address ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $server->status_badge }}">{{ ucfirst($server->status) }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $server->power_badge }}">{{ ucfirst($server->power_status) }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ $server->virtfusion_server_id ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $servers->links() }}
    </div>
</div>
@endsection
