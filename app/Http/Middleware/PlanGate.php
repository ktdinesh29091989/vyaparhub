<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlanGate
{
    /**
     * Blocks a feature once the free-plan limit is hit, redirecting to /upgrade
     * with a flash message explaining exactly which limit was hit.
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if ($user->isPro()) {
            return $next($request);
        }

        $reason = match ($feature) {
            'products' => $this->productsReason($user),
            'orders' => $this->ordersReason($user),
            'pdf' => 'PDF export is a Pro feature. Upgrade to Pro to download P&L reports as PDF.',
            'channels' => 'The Sales Channels report is a Pro feature. Upgrade to Pro to see channel-by-channel performance.',
            'inventory' => 'Stock Health is a Pro feature. Upgrade to Pro to see stock adequacy and sales velocity.',
            default => null,
        };

        if ($reason) {
            return redirect()->route('upgrade')->with('upgrade_reason', $reason);
        }

        return $next($request);
    }

    private function productsReason(User $user): ?string
    {
        $count = $user->products()->count();

        return $count >= User::FREE_PRODUCT_LIMIT
            ? "You've reached the Free plan limit of ".User::FREE_PRODUCT_LIMIT." products (currently {$count}). Upgrade to Pro for unlimited products."
            : null;
    }

    private function ordersReason(User $user): ?string
    {
        $count = $user->orders()
            ->whereBetween('order_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        return $count >= User::FREE_MONTHLY_ORDER_LIMIT
            ? "You've reached the Free plan limit of ".User::FREE_MONTHLY_ORDER_LIMIT." orders this month (currently {$count}). Upgrade to Pro for unlimited orders."
            : null;
    }
}
