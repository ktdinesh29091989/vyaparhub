<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderImportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UpgradeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('landing');
})->name('landing');

Route::view('privacy', 'privacy')->name('privacy');

// Guest-only routes (registration, login, forgot-password request)
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
});

// Deliberately NOT behind 'guest' middleware — the emailed reset link must work even if the
// browser has an active session (e.g. a stale/different login), otherwise 'guest' silently
// bounces the click straight to /dashboard before the "new password" form ever renders.
// NewPasswordController::store() resets the target user found via the signed token/email in
// the form, independent of whoever (if anyone) is currently authenticated in this session.
Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');

// Razorpay calls this directly — no session, no CSRF token available (exempted in bootstrap/app.php).
Route::post('razorpay/webhook', [SubscriptionController::class, 'webhook'])->name('razorpay.webhook');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Email verification
    Route::get('email/verify', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')->name('verification.send');

    Route::get('onboarding', [OnboardingController::class, 'index'])->name('onboarding');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Products (create/store gated by the free-plan product limit)
    Route::get('products/create', [ProductController::class, 'create'])->middleware('plan.gate:products')->name('products.create');
    Route::post('products', [ProductController::class, 'store'])->middleware('plan.gate:products')->name('products.store');
    Route::get('products/import/sample', [ProductController::class, 'downloadSampleCsv'])->name('products.import.sample');
    Route::get('products/import/template', [ProductController::class, 'downloadTemplateCsv'])->name('products.import.template');
    Route::post('products/import', [ProductController::class, 'importCsv'])->middleware('plan.gate:products')->name('products.import');
    Route::resource('products', ProductController::class)->except(['show', 'create', 'store']);
    Route::post('products/{product}/stock', [ProductController::class, 'adjustStock'])->name('products.stock');
    Route::post('products/{product}/stock/add', [ProductController::class, 'addStock'])->name('products.stock.add');

    // Orders (create/store/quick/import gated by the free-plan monthly order limit)
    Route::get('orders/create', [OrderController::class, 'create'])->middleware('plan.gate:orders')->name('orders.create');
    Route::post('orders', [OrderController::class, 'store'])->middleware('plan.gate:orders')->name('orders.store');
    Route::get('orders/quick', [OrderController::class, 'quick'])->middleware('plan.gate:orders')->name('orders.quick');
    Route::get('orders/import', [OrderImportController::class, 'form'])->middleware('plan.gate:orders')->name('orders.import.form');
    Route::post('orders/import', [OrderImportController::class, 'import'])->middleware('plan.gate:orders')->name('orders.import');
    Route::get('orders/import/sample', [OrderImportController::class, 'sample'])->name('orders.import.sample');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('orders/{order}/return', [OrderController::class, 'recordReturn'])->name('orders.return');
    Route::resource('orders', OrderController::class)->only(['index', 'show', 'destroy']);

    // Reports (Stock Health & PDF export are Pro-only)
    Route::get('reports/profit', [ReportController::class, 'profit'])->name('reports.profit');
    Route::get('reports/inventory', [ReportController::class, 'inventory'])->middleware('plan.gate:inventory')->name('reports.inventory');
    Route::get('reports/pnl', [ReportController::class, 'pnl'])->name('reports.pnl');
    Route::get('reports/pnl/export', [ReportController::class, 'pnlExport'])->name('reports.pnl.export');
    Route::get('reports/pnl/pdf', [ReportController::class, 'pnlPdf'])->middleware('plan.gate:pdf')->name('reports.pnl.pdf');

    // Expenses
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    // Sales Channels report — Pro-only
    Route::get('channels', [ChannelController::class, 'index'])->middleware('plan.gate:channels')->name('channels.index');
    Route::post('channels', [ChannelController::class, 'store'])->middleware('plan.gate:channels')->name('channels.store');
    Route::put('channels', [ChannelController::class, 'update'])->middleware('plan.gate:channels')->name('channels.update');

    // Billing
    Route::get('upgrade', [UpgradeController::class, 'index'])->name('upgrade');
    Route::post('subscription/create', [SubscriptionController::class, 'create'])->name('subscription.create');
    Route::post('subscription/verify', [SubscriptionController::class, 'verify'])->name('subscription.verify');

    // Profile
    Route::get('profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

// Admin (site-owner only — see App\Http\Middleware\EnsureIsAdmin)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('users', [AdminController::class, 'users'])->name('users');
    Route::post('users/{user}/grant-pro', [AdminController::class, 'grantPro'])->name('users.grant-pro');
    Route::get('grants', [AdminController::class, 'grants'])->name('grants');
    Route::get('payments', [AdminController::class, 'payments'])->name('payments');
});
