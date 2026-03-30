<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['user', 'order.server'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->paginate(25)->withQueryString();
        return view('admin.invoices.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        $users = User::orderBy('name')->get();
        $selectedUser = $request->filled('user_id') ? User::find($request->user_id) : null;

        return view('admin.invoices.create', compact('users', 'selectedUser'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'description' => ['required', 'string', 'max:255'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:paid,unpaid,cancelled'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $subtotal = (float) $validated['subtotal'];
        $taxRate = (float) $validated['tax_rate'];
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = round($subtotal + $taxAmount, 2);

        $invoice = Invoice::create([
            'user_id' => $validated['user_id'],
            'order_id' => null,
            'invoice_number' => Invoice::generateNumber(),
            'status' => $validated['status'],
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'currency' => 'EUR',
            'due_date' => $validated['due_date'],
            'paid_at' => $validated['status'] === 'paid' ? now() : null,
        ]);

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', "Factuur {$invoice->invoice_number} aangemaakt.");
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['user', 'order.server', 'order.package']);
        return view('admin.invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load('user');
        return view('admin.invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:paid,unpaid,cancelled'],
            'due_date' => ['required', 'date'],
        ]);

        $subtotal = (float) $validated['subtotal'];
        $taxRate = (float) $validated['tax_rate'];
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = round($subtotal + $taxAmount, 2);

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'status' => $validated['status'],
            'due_date' => $validated['due_date'],
            'paid_at' => $validated['status'] === 'paid' && !$invoice->paid_at ? now() : $invoice->paid_at,
        ]);

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', 'Factuur bijgewerkt.');
    }

    public function destroy(Invoice $invoice)
    {
        $number = $invoice->invoice_number;
        $invoice->delete();

        return redirect()->route('admin.invoices.index')
            ->with('success', "Factuur {$number} verwijderd.");
    }

    public function markPaid(Invoice $invoice)
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return back()->with('success', "Factuur {$invoice->invoice_number} als betaald gemarkeerd.");
    }
}
