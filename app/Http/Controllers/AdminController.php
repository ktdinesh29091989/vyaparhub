<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PlanGrant;
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

    public function grantPro(Request $request, User $user)
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:3650'],
            'plan_type' => ['required', 'in:monthly,annual'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $previousPlan = $user->plan;
        $previousExpiresAt = $user->plan_expires_at;

        $user->grantOrExtendPro($validated['days'], $validated['plan_type']);

        PlanGrant::create([
            'user_id' => $user->id,
            'granted_by' => $request->user()->id,
            'previous_plan' => $previousPlan,
            'previous_expires_at' => $previousExpiresAt,
            'new_plan' => 'pro',
            'new_plan_type' => $validated['plan_type'],
            'new_expires_at' => $user->plan_expires_at,
            'days_granted' => $validated['days'],
            'reason' => $validated['reason'],
        ]);

        return back()->with('status', "Granted {$validated['days']} days of Pro to {$user->name} — new expiry ".$user->plan_expires_at->format('d M Y').'.');
    }

    public function grants(Request $request)
    {
        $filterUser = $request->filled('user_id') ? User::find($request->integer('user_id')) : null;

        $grants = PlanGrant::with(['user', 'admin'])
            ->when($filterUser, fn ($q) => $q->where('user_id', $filterUser->id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.grants', compact('grants', 'filterUser'));
    }
}
