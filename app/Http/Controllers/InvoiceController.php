<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Auth::user()->invoices()
            ->with('order.package')
            ->latest()
            ->paginate(15);

        return view('dashboard.invoices.index', compact('invoices'));
    }

    public function show($id)
    {
        $invoice = Auth::user()->invoices()
            ->with(['order.package', 'order.server'])
            ->findOrFail($id);

        return view('dashboard.invoices.show', compact('invoice'));
    }
}
