<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

// ── Public ───────────────────────────────────────────────────────────
Route::view('/', 'welcome')->name('home');

// ── Auth ─────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

// ── Webhooks (no CSRF) ──────────────────────────────────────────────
Route::post('/webhooks/mollie', [WebhookController::class, 'mollie'])->name('webhooks.mollie');

// ── Dashboard (authenticated) ────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Servers
    Route::get('/servers', [ServerController::class, 'index'])->name('servers.index');
    Route::get('/servers/create', [ServerController::class, 'create'])->name('servers.create');
    Route::post('/servers', [ServerController::class, 'store'])->name('servers.store');
    Route::get('/servers/{server}', [ServerController::class, 'show'])->name('servers.show');
    Route::post('/servers/{server}/power', [ServerController::class, 'power'])->name('servers.power');
    Route::get('/servers/{server}/upgrade', [ServerController::class, 'upgrade'])->name('servers.upgrade');
    Route::post('/servers/{server}/upgrade', [ServerController::class, 'processUpgrade'])->name('servers.upgrade.process');
    Route::get('/servers/{server}/downgrade', [ServerController::class, 'downgrade'])->name('servers.downgrade');
    Route::post('/servers/{server}/downgrade', [ServerController::class, 'processDowngrade'])->name('servers.downgrade.process');
    Route::post('/servers/{server}/reset-password', [ServerController::class, 'resetPassword'])->name('servers.reset-password');
    Route::get('/servers/{server}/console', [ServerController::class, 'console'])->name('servers.console');
    Route::put('/servers/{server}/rename', [ServerController::class, 'rename'])->name('servers.rename');
    Route::delete('/servers/{server}', [ServerController::class, 'destroy'])->name('servers.destroy');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

// ── Admin ────────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/users', [AdminDashboardController::class, 'users'])->name('admin.users');
    Route::get('/servers', [AdminDashboardController::class, 'servers'])->name('admin.servers');
    Route::get('/orders', [AdminDashboardController::class, 'orders'])->name('admin.orders');

    // Packages
    Route::get('/packages', [AdminDashboardController::class, 'packages'])->name('admin.packages');
    Route::get('/packages/create', [AdminDashboardController::class, 'createPackage'])->name('admin.packages.create');
    Route::post('/packages', [AdminDashboardController::class, 'storePackage'])->name('admin.packages.store');
    Route::get('/packages/{package}/edit', [AdminDashboardController::class, 'editPackage'])->name('admin.packages.edit');
    Route::put('/packages/{package}', [AdminDashboardController::class, 'updatePackage'])->name('admin.packages.update');
    Route::post('/packages/sync', [AdminDashboardController::class, 'syncPackages'])->name('admin.packages.sync');

    // Sync users & servers from VirtFusion
    Route::post('/users/sync', [AdminDashboardController::class, 'syncUsers'])->name('admin.users.sync');
});
