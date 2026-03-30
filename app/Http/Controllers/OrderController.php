<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Auth::user()->orders()
            ->with(['package', 'server'])
            ->latest()
            ->paginate(15);

        return view('dashboard.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Auth::user()->orders()
            ->with(['package', 'server', 'invoice'])
            ->findOrFail($id);

        return view('dashboard.orders.show', compact('order'));
    }
}
