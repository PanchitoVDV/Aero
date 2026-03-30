@extends('layouts.app')
@section('title', 'Gebruikers Beheer')
@section('header', 'Gebruikers Beheer')

@section('content')
<div class="bg-white rounded-xl border border-gray-200">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Naam</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Servers</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">VF User ID</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Geregistreerd</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $user->id }}</td>
                    <td class="px-6 py-4">
                        <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                        @if($user->company)<p class="text-xs text-gray-500">{{ $user->company }}</p>@endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $user->servers_count }}</td>
                    <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ $user->virtfusion_user_id ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $user->created_at->format('d-m-Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $users->links() }}
    </div>
</div>
@endsection
