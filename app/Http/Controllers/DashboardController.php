<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProfitCalculator;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, ProfitCalculator $profit)
    {
        $user = $request->user();
        $user->ensureDefaultChannels();

        // product_id => category slug, for attributing order items to a category.
        $categoryOf = $user->products()->pluck('category', 'id')->all();
        $userCategories = array_values(array_unique($categoryOf));

        $selectedCategory = $request->string('category')->toString();
        $selectedCategory = array_key_exists($selectedCategory, Product::CATEGORIES) ? $selectedCategory : null;

        $products = $user->products();
        if ($selectedCategory) {
            $products = (clone $products)->where('category', $selectedCategory);
        }

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
        if ($selectedCategory) {
            $orders = (clone $orders)->whereHas('items.product', fn ($q) => $q->where('category', $selectedCategory));
        }
        $today = now()->toDateString();

        $todaysDeliveredOrders = (clone $orders)->with('items')->where('status', 'delivered')->whereDate('order_date', $today)->get();
        if ($selectedCategory) {
            $todaysItems = $todaysDeliveredOrders->flatMap->items
                ->filter(fn ($i) => ($categoryOf[$i->product_id] ?? null) === $selectedCategory);
            $todaysRevenue = (float) $todaysItems->sum(fn ($i) => $i->quantity * $i->sale_price);
            $todaysProfit = (float) ($profit->byCategory($todaysDeliveredOrders, $categoryOf)[$selectedCategory]['net_profit'] ?? 0);
        } else {
            $todaysRevenue = (float) $todaysDeliveredOrders->flatMap->items->sum(fn ($i) => $i->quantity * $i->sale_price);
            $todaysProfit = $profit->aggregate($todaysDeliveredOrders)['net_profit'];
        }

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
            ->map(function ($dayOrders) use ($selectedCategory, $categoryOf) {
                $items = $dayOrders->flatMap->items;
                if ($selectedCategory) {
                    $items = $items->filter(fn ($i) => ($categoryOf[$i->product_id] ?? null) === $selectedCategory);
                }
                return $items->sum(fn ($i) => $i->quantity * $i->sale_price);
            });

        $chart = ['labels' => [], 'data' => []];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $chart['labels'][] = $date->format('D');
            $chart['data'][] = (float) ($revenueByDay[$date->toDateString()] ?? 0);
        }

        $recentOrders = (clone $orders)->with('channel')->latest('id')->limit(5)->get();

        // Profit-per-category breakdown (this month, delivered orders), shown only when the
        // seller's catalog spans more than one category. Every category the seller has
        // products in gets an entry, even ₹0, so the chart doesn't silently drop a category.
        $categoryBreakdown = [];
        if (count($userCategories) > 1) {
            $monthOrders = $user->orders()->with('items')
                ->where('status', 'delivered')
                ->whereBetween('order_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->get();
            $monthByCategory = $profit->byCategory($monthOrders, $categoryOf);

            foreach ($userCategories as $cat) {
                $sums = $monthByCategory[$cat] ?? ['revenue' => 0.0, 'net_profit' => 0.0, 'units_sold' => 0];
                $categoryBreakdown[] = [
                    'label' => Product::CATEGORIES[$cat] ?? ucfirst(str_replace('_', ' ', $cat)),
                    'net_profit' => $sums['net_profit'],
                    'color' => Product::CATEGORY_CHART_COLORS[$cat] ?? '#64748b',
                ];
            }
            usort($categoryBreakdown, fn ($a, $b) => $b['net_profit'] <=> $a['net_profit']);
        }

        return view('dashboard', compact(
            'stats', 'lowStock', 'cards', 'chart', 'recentOrders', 'categoryBreakdown', 'selectedCategory'
        ));
    }
}
