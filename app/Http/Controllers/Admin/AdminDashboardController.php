<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Server;
use App\Models\Order;
use App\Models\Package;
use App\Services\VirtFusionService;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_servers' => Server::count(),
            'active_servers' => Server::where('status', 'active')->count(),
            'total_revenue' => Order::where('status', 'paid')->sum('total'),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'monthly_revenue' => Order::where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->sum('total'),
        ];

        $recentOrders = Order::with(['user', 'package'])->latest()->take(10)->get();
        $recentUsers = User::latest()->take(10)->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'recentUsers'));
    }

    public function users()
    {
        $users = User::withCount('servers')->latest()->paginate(25);
        return view('admin.users', compact('users'));
    }

    public function servers()
    {
        $servers = Server::with(['user', 'package'])->latest()->paginate(25);
        return view('admin.servers', compact('servers'));
    }

    public function packages()
    {
        $packages = Package::orderBy('sort_order')->get();
        return view('admin.packages.index', compact('packages'));
    }

    public function createPackage()
    {
        return view('admin.packages.create');
    }

    public function storePackage(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'virtfusion_package_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'in:vps,dedicated,game'],
            'memory' => ['required', 'integer', 'min:256'],
            'storage' => ['required', 'integer', 'min:5'],
            'cpu_cores' => ['required', 'integer', 'min:1'],
            'traffic' => ['required', 'integer', 'min:0'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_quarterly' => ['nullable', 'numeric', 'min:0'],
            'price_yearly' => ['nullable', 'numeric', 'min:0'],
            'setup_fee' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        Package::create($validated);

        return redirect()->route('admin.packages')->with('success', 'Pakket aangemaakt.');
    }

    public function editPackage(Package $package)
    {
        return view('admin.packages.edit', compact('package'));
    }

    public function updatePackage(\Illuminate\Http\Request $request, Package $package)
    {
        $validated = $request->validate([
            'virtfusion_package_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'in:vps,dedicated,game'],
            'memory' => ['required', 'integer', 'min:256'],
            'storage' => ['required', 'integer', 'min:5'],
            'cpu_cores' => ['required', 'integer', 'min:1'],
            'traffic' => ['required', 'integer', 'min:0'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_quarterly' => ['nullable', 'numeric', 'min:0'],
            'price_yearly' => ['nullable', 'numeric', 'min:0'],
            'setup_fee' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        $package->update($validated);

        return redirect()->route('admin.packages')->with('success', 'Pakket bijgewerkt.');
    }

    public function syncPackages(VirtFusionService $virtfusion)
    {
        try {
            $vfPackages = $virtfusion->getPackages();

            foreach ($vfPackages['data'] ?? [] as $vfPkg) {
                Package::updateOrCreate(
                    ['virtfusion_package_id' => $vfPkg['id']],
                    [
                        'name' => $vfPkg['name'],
                        'memory' => $vfPkg['memory'],
                        'storage' => $vfPkg['primaryStorage'],
                        'cpu_cores' => $vfPkg['cpuCores'],
                        'traffic' => $vfPkg['traffic'],
                        'network_speed_in' => $vfPkg['primaryNetworkSpeedIn'] ?? 0,
                        'network_speed_out' => $vfPkg['primaryNetworkSpeedOut'] ?? 0,
                        'price_monthly' => 0, // Must be set manually
                    ]
                );
            }

            return back()->with('success', 'Pakketten gesynchroniseerd met VirtFusion. Stel de prijzen in.');
        } catch (\Exception $e) {
            return back()->with('error', 'Synchronisatie mislukt: ' . $e->getMessage());
        }
    }

    public function orders()
    {
        $orders = Order::with(['user', 'package', 'server'])->latest()->paginate(25);
        return view('admin.orders', compact('orders'));
    }
}
