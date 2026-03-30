@extends('layouts.app')
@section('title', 'Nieuwe Factuur')
@section('header', 'Nieuwe Factuur Aanmaken')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('admin.invoices.store') }}">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Klant &amp; Omschrijving</h2>

            <div class="space-y-4">
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1.5">Klant</label>
                    <select name="user_id" id="user_id" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                        <option value="">Selecteer een klant...</option>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ (old('user_id', $selectedUser?->id) == $user->id) ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                        @endforeach
                    </select>
                    @error('user_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">Omschrijving</label>
                    <input type="text" name="description" id="description" value="{{ old('description') }}" placeholder="Bijv. Server hosting maart 2026" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                    @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1.5">Notities (optioneel)</label>
                    <textarea name="notes" id="notes" rows="2" placeholder="Interne notities..."
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Bedragen</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="subtotal" class="block text-sm font-medium text-gray-700 mb-1.5">Subtotaal (excl. BTW)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">&euro;</span>
                        <input type="number" name="subtotal" id="subtotal" value="{{ old('subtotal', '0.00') }}" step="0.01" min="0" required
                            class="w-full pl-7 pr-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                    </div>
                    @error('subtotal')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-1.5">BTW Tarief</label>
                    <div class="relative">
                        <input type="number" name="tax_rate" id="tax_rate" value="{{ old('tax_rate', '21') }}" step="0.01" min="0" max="100" required
                            class="w-full pr-8 px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                    </div>
                    @error('tax_rate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-500">BTW</span>
                    <span class="text-gray-700" id="calcTax">&euro;0,00</span>
                </div>
                <div class="flex justify-between text-base font-bold border-t border-gray-200 pt-2 mt-2">
                    <span class="text-gray-900">Totaal</span>
                    <span class="text-gray-900" id="calcTotal">&euro;0,00</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Status &amp; Vervaldatum</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                    <select name="status" id="status" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                        <option value="unpaid" {{ old('status') === 'unpaid' ? 'selected' : '' }}>Openstaand</option>
                        <option value="paid" {{ old('status') === 'paid' ? 'selected' : '' }}>Betaald</option>
                        <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Geannuleerd</option>
                    </select>
                </div>

                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1.5">Vervaldatum</label>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date', now()->addDays(14)->format('Y-m-d')) }}" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.invoices.index') }}" class="text-sm text-gray-600 hover:text-gray-900 transition">Annuleren</a>
            <button type="submit" class="bg-brand-600 text-white font-semibold px-6 py-2.5 rounded-lg hover:bg-brand-700 transition">
                Factuur Aanmaken
            </button>
        </div>
    </form>
</div>

<script>
(function() {
    const subtotalEl = document.getElementById('subtotal');
    const taxRateEl = document.getElementById('tax_rate');
    const calcTax = document.getElementById('calcTax');
    const calcTotal = document.getElementById('calcTotal');

    function fmt(v) { return '\u20AC' + v.toFixed(2).replace('.', ','); }

    function calc() {
        const sub = parseFloat(subtotalEl.value) || 0;
        const rate = parseFloat(taxRateEl.value) || 0;
        const tax = sub * (rate / 100);
        calcTax.textContent = fmt(tax);
        calcTotal.textContent = fmt(sub + tax);
    }

    subtotalEl.addEventListener('input', calc);
    taxRateEl.addEventListener('input', calc);
    calc();
})();
</script>
@endsection
