<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $servers = $user->servers()->with('package')->latest()->get();
        $recentOrders = $user->orders()->with('package')->latest()->take(5)->get();
        $invoices = $user->invoices()->latest()->take(5)->get();

        return view('dashboard.index', compact('servers', 'recentOrders', 'invoices'));
    }
}
