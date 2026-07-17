<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'pro_users' => User::where('plan', 'pro')->count(),
            'free_users' => User::where('plan', 'free')->count(),
            'new_users_this_month' => User::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'total_revenue' => (float) Payment::where('status', 'captured')->sum('amount'),
            'revenue_this_month' => (float) Payment::where('status', 'captured')
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'failed_payments_this_month' => Payment::where('status', 'failed')
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
        ];

        $recentPayments = Payment::with('user')->latest()->limit(10)->get();
        $recentUsers = User::latest()->limit(10)->get();

        // Pro users whose plan expires within the next 7 days (strictly upcoming, not already
        // lapsed) — a deliberately tighter definition than User::isExpiringSoon(), which uses
        // Carbon's diffInDays() and therefore also matches plans that already expired days ago.
        $renewalsDueSoon = User::where('plan', 'pro')
            ->whereNotNull('plan_expires_at')
            ->whereBetween('plan_expires_at', [now(), now()->addDays(7)])
            ->orderBy('plan_expires_at')
            ->get();

        return view('admin.index', compact('stats', 'recentPayments', 'recentUsers', 'renewalsDueSoon'));
    }

    public function users()
    {
        $users = User::withCount(['products', 'orders'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function payments(Request $request)
    {
        $status = $request->string('status')->toString();

        $query = Payment::with('user')->latest();
        if (in_array($status, ['captured', 'failed'], true)) {
            $query->where('status', $status);
        }

        $payments = $query->paginate(20)->withQueryString();

        return view('admin.payments', compact('payments', 'status'));
    }
}
