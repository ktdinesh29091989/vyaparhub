<?php

namespace App\Http\Controllers;

use App\Services\ProfitCalculator;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, ProfitCalculator $profit)
    {
        $user = $request->user();
        $user->ensureDefaultChannels();

        $products = $user->products();

        $stats = [
            'product_count' => (clone $products)->count(),
            'units_in_stock' => (int) (clone $products)->sum('stock'),
            'stock_value' => (float) (clone $products)->selectRaw('COALESCE(SUM(stock * cost_price), 0) as v')->value('v'),
            'low_stock_count' => (clone $products)->whereColumn('stock', '<=', 'stock_threshold')->count(),
        ];

        $lowStock = (clone $products)
            ->whereColumn('stock', '<=', 'stock_threshold')
            ->orderBy('stock')
            ->limit(5)
            ->get();

        $orders = $user->orders();
        $today = now()->toDateString();

        $todaysDeliveredOrders = (clone $orders)->with('items')->where('status', 'delivered')->whereDate('order_date', $today)->get();
        $todaysRevenue = (float) $todaysDeliveredOrders->flatMap->items->sum(fn ($i) => $i->quantity * $i->sale_price);
        $todaysProfit = $profit->aggregate($todaysDeliveredOrders)['net_profit'];

        $cards = [
            'todays_revenue' => $todaysRevenue,
            'todays_profit' => $todaysProfit,
            'month_orders' => (clone $orders)->whereBetween('order_date', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'low_stock_products' => $stats['low_stock_count'],
            'pending_actions' => (clone $orders)->whereIn('status', ['placed', 'shipped'])->count(),
        ];

        // Last 7 days' revenue (delivered orders only), for the bar chart.
        $recentDeliveredOrders = (clone $orders)->with('items')
            ->where('status', 'delivered')
            ->whereDate('order_date', '>=', now()->subDays(6)->toDateString())
            ->whereDate('order_date', '<=', $today)
            ->get();

        $revenueByDay = $recentDeliveredOrders
            ->groupBy(fn ($o) => $o->order_date->toDateString())
            ->map(fn ($dayOrders) => $dayOrders->flatMap->items->sum(fn ($i) => $i->quantity * $i->sale_price));

        $chart = ['labels' => [], 'data' => []];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $chart['labels'][] = $date->format('D');
            $chart['data'][] = (float) ($revenueByDay[$date->toDateString()] ?? 0);
        }

        $recentOrders = (clone $orders)->with('channel')->latest('id')->limit(5)->get();

        return view('dashboard', compact('stats', 'lowStock', 'cards', 'chart', 'recentOrders'));
    }
}
