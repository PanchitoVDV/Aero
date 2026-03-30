@extends('layouts.app')
@section('title', 'Factuur Bewerken - ' . $invoice->invoice_number)
@section('header', 'Factuur Bewerken')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $invoice->invoice_number }}</h2>
                <p class="text-sm text-gray-500">Klant: {{ $invoice->user->name }} ({{ $invoice->user->email }})</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : ($invoice->status === 'cancelled' ? 'bg-gray-100 text-gray-600' : 'bg-yellow-100 text-yellow-800') }}">
                {{ $invoice->status === 'paid' ? 'Betaald' : ($invoice->status === 'cancelled' ? 'Geannuleerd' : 'Openstaand') }}
            </span>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Bedragen</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="subtotal" class="block text-sm font-medium text-gray-700 mb-1.5">Subtotaal (excl. BTW)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">&euro;</span>
                        <input type="number" name="subtotal" id="subtotal" value="{{ old('subtotal', $invoice->subtotal) }}" step="0.01" min="0" required
                            class="w-full pl-7 pr-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                    </div>
                    @error('subtotal')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-1.5">BTW Tarief</label>
                    <div class="relative">
                        <input type="number" name="tax_rate" id="tax_rate" value="{{ old('tax_rate', $invoice->tax_rate) }}" step="0.01" min="0" max="100" required
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
                        <option value="unpaid" {{ old('status', $invoice->status) === 'unpaid' ? 'selected' : '' }}>Openstaand</option>
                        <option value="paid" {{ old('status', $invoice->status) === 'paid' ? 'selected' : '' }}>Betaald</option>
                        <option value="cancelled" {{ old('status', $invoice->status) === 'cancelled' ? 'selected' : '' }}>Geannuleerd</option>
                    </select>
                </div>

                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1.5">Vervaldatum</label>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $invoice->due_date->format('Y-m-d')) }}" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-sm">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-sm text-gray-600 hover:text-gray-900 transition">Annuleren</a>
            <button type="submit" class="bg-brand-600 text-white font-semibold px-6 py-2.5 rounded-lg hover:bg-brand-700 transition">
                Opslaan
            </button>
        </div>
    </form>

    <div class="mt-6 pt-6 border-t border-gray-200 max-w-2xl">
        <form method="POST" action="{{ route('admin.invoices.destroy', $invoice) }}" onsubmit="return confirm('Weet je zeker dat je deze factuur wilt verwijderen?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-red-600 hover:text-red-800 transition">Factuur verwijderen</button>
        </form>
    </div>
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
