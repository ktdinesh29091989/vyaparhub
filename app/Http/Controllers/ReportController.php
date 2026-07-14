<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\InventoryAnalyzer;
use App\Services\ProfitCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /** Profit & Loss statement (date range, default current calendar month) with GST summary. */
    public function pnl(Request $request, ProfitCalculator $profit)
    {
        $user = $request->user();

        // Free plan: basic today + this-week totals only — no date-range report or table.
        if (! $user->isPro()) {
            $today = now();
            $todayStatement = $this->buildStatement($user, $today->copy()->startOfDay(), $today->copy()->endOfDay(), $profit);
            $weekStatement = $this->buildStatement($user, $today->copy()->startOfWeek(), $today->copy()->endOfWeek(), $profit);

            return view('reports.pnl', [
                'todayPnl' => $todayStatement['pnl'],
                'weekPnl' => $weekStatement['pnl'],
            ]);
        }

        [$start, $end] = $this->resolveRange($request);

        $statement = $this->buildStatement($user, $start, $end, $profit);

        // Equal-length prior period for a simple trend comparison.
        $days = $start->diffInDays($end) + 1;
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1);
        $prevStatement = $this->buildStatement($user, $prevStart, $prevEnd, $profit);

        return view('reports.pnl', array_merge(
            $this->pnlViewData($request, $statement),
            [
                'prevNet' => $prevStatement['pnl']['net_profit'],
                'prevRevenue' => $prevStatement['pnl']['revenue'],
                'categories' => Expense::CATEGORIES,
            ]
        ));
    }

    /** Shared view-data assembly for both the HTML page and the PDF. */
    private function pnlViewData(Request $request, array $statement): array
    {
        [$start, $end] = $this->resolveRange($request);

        return [
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'rangeLabel' => $start->format('d M Y').' – '.$end->format('d M Y'),
            'pnl' => $statement['pnl'],
            'gst' => $statement['gst'],
            'expensesByCategory' => $statement['expenses_by_category'],
            'expenses' => $statement['expenses'],
            'channels' => $statement['channels'],
            'deliveredOrders' => $statement['delivered_orders'],
            'orderCount' => $statement['order_count'],
        ];
    }

    public function pnlExport(Request $request, ProfitCalculator $profit): StreamedResponse
    {
        [$start, $end] = $this->resolveRange($request);
        $s = $this->buildStatement($request->user(), $start, $end, $profit);
        $p = $s['pnl'];
        $g = $s['gst'];

        $lines = [
            ['VyaparHub Profit & Loss', $start->format('d M Y').' - '.$end->format('d M Y')],
            [],
            ['Total Revenue', $p['revenue']],
            ['Total Cost', -$p['cogs']],
            ['Gross Profit', $p['gross_profit']],
            ['Total Shipping Spent', -$p['shipping']],
            ['Total Commission/Fees', -$p['commission']],
            ['Total Return Loss', -$p['return_loss']],
            ['Other expenses', -$p['expenses']],
            ['NET PROFIT', $p['net_profit']],
            [],
            ['GST output (collected)', $g['output']],
            ['GST input (paid on purchases)', $g['input']],
            ['Net GST payable', $g['net']],
        ];

        return response()->streamDownload(function () use ($lines) {
            $out = fopen('php://output', 'w');
            foreach ($lines as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'pnl-'.$start->format('Y-m-d').'-to-'.$end->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /** Same statement, rendered as a printable PDF via barryvdh/laravel-dompdf. */
    public function pnlPdf(Request $request, ProfitCalculator $profit)
    {
        [$start, $end] = $this->resolveRange($request);
        $statement = $this->buildStatement($request->user(), $start, $end, $profit);

        $pdf = Pdf::loadView('reports.pnl-pdf', $this->pnlViewData($request, $statement));

        return $pdf->download('pnl-'.$start->format('Y-m-d').'-to-'.$end->format('Y-m-d').'.pdf');
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolveRange(Request $request): array
    {
        $from = $request->date('from');
        $to = $request->date('to');

        if ($from && $to) {
            return [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
        }

        return [now()->startOfMonth(), now()->endOfMonth()];
    }

    private function buildStatement($user, Carbon $start, Carbon $end, ProfitCalculator $profit): array
    {
        $orders = $user->orders()->with(['items', 'channel'])
            ->whereBetween('order_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->get();

        $agg = $profit->aggregate($orders);

        $expenses = $user->expenses()
            ->whereBetween('spent_on', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->orderBy('spent_on')
            ->get();
        $expensesTotal = (float) $expenses->sum('amount');

        // Delivered orders only: the figures the summary cards are built from.
        $deliveredOrders = $orders->where('status', 'delivered')->values();
        $revenueDelivered = (float) $deliveredOrders->flatMap->items->sum(fn ($i) => $i->quantity * $i->sale_price);
        $costDelivered = (float) $deliveredOrders->flatMap->items->sum(fn ($i) => $i->quantity * $i->cost_price);
        $shippingDelivered = (float) $deliveredOrders->sum('shipping_charge');
        $commissionDelivered = (float) $deliveredOrders->sum('commission_amount');
        $grossProfitDelivered = $revenueDelivered - $costDelivered;

        // Return loss: from orders that came back (returned / partially_returned) in this period.
        $returnedOrders = $orders->whereIn('status', ['returned', 'partially_returned'])->values();
        $returnLoss = (float) $returnedOrders->sum(fn ($o) => $profit->forOrder($o)['totals']['return_loss']);

        $netProfit = $grossProfitDelivered - $shippingDelivered - $commissionDelivered - $returnLoss;

        $pnl = [
            'revenue' => $revenueDelivered,
            'cogs' => $costDelivered,
            'gross_profit' => $grossProfitDelivered,
            'commission' => $commissionDelivered,
            'shipping' => $shippingDelivered,
            'return_loss' => $returnLoss,
            'expenses' => $expensesTotal,
            'net_profit' => $netProfit,
            'units_sold' => $agg['units_sold'],
            'units_returned' => $agg['units_returned'],
            'margin_pct' => $revenueDelivered > 0 ? $netProfit / $revenueDelivered * 100 : 0,
        ];

        // Per-channel revenue & profit (across all orders in range, for context below the cards).
        $channels = [];
        foreach ($orders->groupBy('channel_id') as $channelOrders) {
            $name = $channelOrders->first()->channel->name ?? 'Direct / Other';
            $cagg = $profit->aggregate($channelOrders);
            $channels[] = [
                'name' => $name,
                'orders' => $channelOrders->count(),
                'revenue' => $cagg['revenue'],
                'net_profit' => $cagg['net_profit'],
            ];
        }
        usort($channels, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        return [
            'pnl' => $pnl,
            'gst' => [
                'output' => $agg['gst'],
                'input' => $agg['input_gst'],
                'net' => $agg['gst'] - $agg['input_gst'],
            ],
            'expenses' => $expenses,
            'expenses_by_category' => $expenses->groupBy('category')->map(fn ($g) => (float) $g->sum('amount'))->sortDesc(),
            'channels' => $channels,
            'delivered_orders' => $deliveredOrders,
            'order_count' => $orders->count(),
        ];
    }

    public function inventory(Request $request, InventoryAnalyzer $analyzer)
    {
        $window = (int) $request->integer('window', 30);
        $window = in_array($window, [30, 60, 90], true) ? $window : 30;
        $deadDays = (int) $request->integer('dead', 60);
        $deadDays = in_array($deadDays, [30, 45, 60, 90], true) ? $deadDays : 60;

        $filter = $request->string('filter')->toString();
        $data = $analyzer->analyze($request->user(), $window, $deadDays);

        $rows = $data['rows'];
        if (in_array($filter, ['top', 'selling', 'slow', 'dead'], true)) {
            $rows = array_values(array_filter($rows, fn ($r) => $r['status'] === $filter));
        }
        // Sort: best sellers first, dead stock by capital locked.
        usort($rows, fn ($a, $b) => $b['sold_window'] <=> $a['sold_window'] ?: $b['capital_locked'] <=> $a['capital_locked']);

        return view('reports.inventory', [
            'rows' => $rows,
            'summary' => $data['summary'],
            'window' => $window,
            'deadDays' => $deadDays,
            'filter' => $filter ?: 'all',
            'stockHealth' => $this->buildStockHealth($request->user()),
        ]);
    }

    /** Simple stock-adequacy table: stock vs threshold, plus a 30-day sales velocity. */
    private function buildStockHealth($user): array
    {
        $since = now()->subDays(30)->toDateString();

        $soldByProduct = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $user->id)
            ->where('orders.status', 'delivered')
            ->where('orders.order_date', '>=', $since)
            ->groupBy('order_items.product_id')
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as qty')
            ->pluck('qty', 'product_id');

        $products = $user->products()->orderBy('stock')->get();

        $rows = [];
        foreach ($products as $p) {
            $avgDailySales = ((int) ($soldByProduct[$p->id] ?? 0)) / 30;
            $daysLeft = $avgDailySales > 0 ? $p->stock / $avgDailySales : null;

            $rows[] = [
                'id' => $p->id,
                'product' => $p,
                'name' => $p->name,
                'sku' => $p->sku,
                'category' => $p->category,
                'stock' => $p->stock,
                'threshold' => $p->stock_threshold,
                'status' => $p->stock === 0 ? 'out' : ($p->stock <= $p->stock_threshold ? 'low' : 'healthy'),
                'avg_daily_sales' => $avgDailySales,
                'days_left' => $daysLeft,
            ];
        }

        return $rows;
    }

    public function profit(Request $request, ProfitCalculator $profit)
    {
        $user = $request->user();

        // Default to the current month; allow ?from / ?to overrides.
        $from = $request->date('from') ?: now()->startOfMonth();
        $to = $request->date('to') ?: now()->endOfMonth();

        $orders = $user->orders()
            ->with(['items', 'channel'])
            ->whereBetween('order_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get();

        $perProduct = $profit->perProduct($orders);

        $totals = [
            'revenue' => array_sum(array_column($perProduct, 'revenue')),
            'net_profit' => array_sum(array_column($perProduct, 'net_profit')),
            'units_sold' => array_sum(array_column($perProduct, 'units_sold')),
            'units_returned' => array_sum(array_column($perProduct, 'units_returned')),
        ];

        $returnedOrders = $orders->whereIn('status', ['returned', 'partially_returned'])->sortByDesc('order_date')->values();

        // Flatten to one row per returned line item, with its loss breakdown.
        $returnRows = [];
        $totalReturnLoss = 0.0;
        foreach ($returnedOrders as $order) {
            $breakdown = $profit->forOrder($order);
            foreach ($breakdown['items'] as $item) {
                if ($item['returned_qty'] <= 0) {
                    continue;
                }
                $revenueLost = $item['returned_qty'] * $item['sale_price'];
                $shippingLost = $item['shipping_per_unit'] * $item['returned_qty'];
                $returnRows[] = [
                    'order_number' => $order->order_number,
                    'order_date' => $order->order_date,
                    'product_name' => $item['product_name'],
                    'channel' => $order->channel->name ?? '—',
                    'channel_badge' => $order->channel->badge_color ?? 'bg-slate-100 text-slate-600',
                    'ordered_qty' => $item['ordered_qty'],
                    'returned_qty' => $item['returned_qty'],
                    'revenue_lost' => $revenueLost,
                    'shipping_lost' => $shippingLost,
                    'net_loss' => $item['return_loss'],
                ];
                $totalReturnLoss += $item['return_loss'];
            }
        }

        $returnsCount = $returnedOrders->count();

        return view('reports.profit', [
            'perProduct' => $perProduct,
            'totals' => $totals,
            'returnRows' => $returnRows,
            'totalReturnLoss' => $totalReturnLoss,
            'returnsCount' => $returnsCount,
            'orderCount' => $orders->count(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }
}
