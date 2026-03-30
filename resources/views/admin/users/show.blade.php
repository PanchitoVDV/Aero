@extends('layouts.app')
@section('title', $user->name . ' - Klant Beheer')
@section('header', $user->name)

@section('header-actions')
<a href="{{ route('admin.invoices.create', ['user_id' => $user->id]) }}" class="bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-brand-700 transition flex items-center gap-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nieuwe Factuur
</a>
@endsection

@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    {{-- Left: User Info --}}
    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-14 h-14 bg-brand-100 rounded-full flex items-center justify-center">
                    <span class="text-xl font-bold text-brand-600">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $user->name }}</h2>
                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                </div>
            </div>

            <div class="space-y-3 border-t border-gray-100 pt-4">
                @if($user->company)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Bedrijf</span>
                    <span class="text-gray-900">{{ $user->company }}</span>
                </div>
                @endif
                @if($user->phone)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Telefoon</span>
                    <span class="text-gray-900">{{ $user->phone }}</span>
                </div>
                @endif
                @if($user->address)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Adres</span>
                    <span class="text-gray-900">{{ $user->address }}, {{ $user->postal_code }} {{ $user->city }}</span>
                </div>
                @endif
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Geregistreerd</span>
                    <span class="text-gray-900">{{ $user->created_at->format('d-m-Y') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">VF User ID</span>
                    <span class="font-mono text-gray-900">{{ $user->virtfusion_user_id ?? '-' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Mollie ID</span>
                    <span class="font-mono text-gray-900">{{ $user->mollie_customer_id ?? '-' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Admin</span>
                    <span class="text-gray-900">{{ $user->is_admin ? 'Ja' : 'Nee' }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Samenvatting</h3>
            <div class="grid grid-cols-3 gap-3">
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900">{{ $user->servers_count }}</div>
                    <div class="text-xs text-gray-500">Servers</div>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900">{{ $user->orders_count }}</div>
                    <div class="text-xs text-gray-500">Orders</div>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900">{{ $user->invoices_count }}</div>
                    <div class="text-xs text-gray-500">Facturen</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Servers, Invoices, Orders --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Servers --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Servers</h3>
            </div>
            @if($servers->isEmpty())
            <div class="px-6 py-8 text-center text-sm text-gray-500">Geen servers.</div>
            @else
            <div class="divide-y divide-gray-100">
                @foreach($servers as $server)
                <a href="{{ route('admin.servers.show', $server) }}" class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full {{ $server->isOnline() ? 'bg-green-500' : 'bg-gray-400' }}"></div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $server->name }}</p>
                            <p class="text-xs text-gray-500">{{ $server->ip_address ?? 'Geen IP' }} &middot; {{ $server->package?->name ?? ($server->custom_ram ? $server->custom_ram.'GB/'.$server->custom_cpu.'vCPU' : '-') }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $server->status_badge }}">{{ ucfirst($server->status) }}</span>
                </a>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Invoices --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Facturen</h3>
                <a href="{{ route('admin.invoices.create', ['user_id' => $user->id]) }}" class="text-xs text-brand-600 hover:underline">+ Nieuwe factuur</a>
            </div>
            @if($invoices->isEmpty())
            <div class="px-6 py-8 text-center text-sm text-gray-500">Geen facturen.</div>
            @else
            <div class="divide-y divide-gray-100">
                @foreach($invoices as $invoice)
                <a href="{{ route('admin.invoices.show', $invoice) }}" class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 transition">
                    <div>
                        <p class="text-sm font-mono font-medium text-gray-900">{{ $invoice->invoice_number }}</p>
                        <p class="text-xs text-gray-500">{{ $invoice->description ?? ($invoice->order?->getTypeLabel() ?? '-') }} &middot; {{ $invoice->created_at->format('d-m-Y') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-gray-900">&euro;{{ number_format($invoice->total, 2, ',', '.') }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : ($invoice->status === 'cancelled' ? 'bg-gray-100 text-gray-600' : 'bg-yellow-100 text-yellow-800') }}">
                            {{ $invoice->status === 'paid' ? 'Betaald' : ($invoice->status === 'cancelled' ? 'Geannuleerd' : 'Open') }}
                        </span>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Orders --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Bestellingen</h3>
            </div>
            @if($orders->isEmpty())
            <div class="px-6 py-8 text-center text-sm text-gray-500">Geen bestellingen.</div>
            @else
            <div class="divide-y divide-gray-100">
                @foreach($orders as $order)
                <div class="flex items-center justify-between px-6 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $order->getTypeLabel() }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $order->server?->name ?? '-' }}
                            &middot; {{ $order->created_at->format('d-m-Y') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-gray-900">&euro;{{ number_format($order->total, 2, ',', '.') }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $order->status === 'paid' ? 'bg-green-100 text-green-800' : ($order->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                            {{ $order->getStatusLabel() }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
