<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Classifies every product as top-seller / selling / slow / dead based on how
 * many units actually sold (net of returns) over a rolling window, and flags
 * capital locked in dead stock plus reorder signals.
 */
class InventoryAnalyzer
{
    /** Orders in these statuses didn't result in a real sale. */
    private const EXCLUDED = ['cancelled', 'returned', 'rto'];

    public function analyze(User $user, int $windowDays = 30, int $deadDays = 60): array
    {
        $windowStart = Carbon::now()->subDays($windowDays)->toDateString();

        $sales = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $user->id)
            ->whereNotIn('orders.status', self::EXCLUDED)
            ->groupBy('order_items.product_id')
            ->selectRaw('order_items.product_id')
            ->selectRaw('SUM(CASE WHEN orders.order_date >= ? THEN (order_items.quantity - order_items.returned_quantity) ELSE 0 END) as sold_window', [$windowStart])
            ->selectRaw('SUM(CASE WHEN orders.order_date >= ? THEN (order_items.quantity - order_items.returned_quantity) * order_items.sale_price ELSE 0 END) as revenue_window', [$windowStart])
            ->selectRaw('SUM(order_items.quantity - order_items.returned_quantity) as sold_total')
            ->selectRaw('MAX(CASE WHEN (order_items.quantity - order_items.returned_quantity) > 0 THEN orders.order_date END) as last_sold')
            ->get()
            ->keyBy('product_id');

        $products = $user->products()->orderBy('name')->get();
        $now = Carbon::now();
        $rows = [];

        foreach ($products as $p) {
            $s = $sales->get($p->id);
            $soldWindow = (int) ($s->sold_window ?? 0);
            $revenueWindow = (float) ($s->revenue_window ?? 0);
            $lastSold = $s && $s->last_sold ? Carbon::parse($s->last_sold) : null;

            $daysSince = $lastSold
                ? $lastSold->diffInDays($now)
                : Carbon::parse($p->created_at)->diffInDays($now);

            $velocity = $soldWindow / max(1, $windowDays);          // units/day
            $daysCover = $velocity > 0 ? (int) round($p->stock / $velocity) : null;
            $capitalLocked = $p->stock * (float) $p->cost_price;

            $rows[] = [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'category' => $p->product_type,
                'stock' => $p->stock,
                'cost_price' => (float) $p->cost_price,
                'sold_window' => $soldWindow,
                'revenue_window' => $revenueWindow,
                'sold_total' => (int) ($s->sold_total ?? 0),
                'last_sold' => $lastSold,
                'days_since_sale' => $daysSince,
                'velocity' => $velocity,
                'days_cover' => $daysCover,
                'capital_locked' => $capitalLocked,
                'status' => null,       // assigned below
                'reorder' => false,
            ];
        }

        $this->classify($rows, $deadDays);

        return [
            'rows' => $rows,
            'summary' => $this->summarise($rows),
            'window_days' => $windowDays,
            'dead_days' => $deadDays,
        ];
    }

    /** Assign top / selling / slow / dead and reorder flags. */
    private function classify(array &$rows, int $deadDays): void
    {
        // Rank sellers to pick the top performers.
        $withSales = array_filter($rows, fn ($r) => $r['sold_window'] > 0);
        usort($withSales, fn ($a, $b) => $b['sold_window'] <=> $a['sold_window']);
        $topIds = array_slice(array_column($withSales, 'id'), 0, max(1, (int) ceil(count($withSales) * 0.2)));
        $topIds = array_flip($topIds);

        foreach ($rows as &$r) {
            if ($r['sold_window'] > 0) {
                $r['status'] = isset($topIds[$r['id']]) ? 'top' : 'selling';
                // Selling but stock won't last two weeks → reorder.
                $r['reorder'] = $r['days_cover'] !== null && $r['days_cover'] < 14;
            } elseif ($r['stock'] > 0 && $r['days_since_sale'] >= $deadDays) {
                $r['status'] = 'dead';
            } else {
                $r['status'] = 'slow';   // some stock or recently added, just no sales in window
            }
        }
        unset($r);
    }

    private function summarise(array $rows): array
    {
        $by = fn ($status) => array_filter($rows, fn ($r) => $r['status'] === $status);

        $dead = $by('dead');
        $top = $by('top');

        return [
            'active_skus' => count($rows),
            'top_count' => count($top),
            'selling_count' => count($by('selling')),
            'slow_count' => count($by('slow')),
            'dead_count' => count($dead),
            'dead_capital' => array_sum(array_column($dead, 'capital_locked')),
            'total_capital' => array_sum(array_column($rows, 'capital_locked')),
            'reorder_count' => count(array_filter($rows, fn ($r) => $r['reorder'])),
        ];
    }
}
