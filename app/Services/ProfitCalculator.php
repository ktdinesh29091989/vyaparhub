<?php

namespace App\Services;

use App\Models\Order;

/**
 * Computes profit for an order and attributes it down to each line item
 * (each saree / kurti), accounting for returns, commission and shipping.
 *
 * Allocation rules:
 *  - A returned unit earns no margin (its goods are back in stock) but the
 *    seller still eats the shipping it consumed.
 *  - Forward + return shipping are split across items by their ORDERED value,
 *    so a fully-returned product still carries its shipping loss.
 *  - Commission is charged only on the SOLD value (marketplaces refund
 *    commission on returns).
 */
class ProfitCalculator
{
    /** Statuses where the whole order counts as not-sold (goods at origin / cancelled). */
    private const FULLY_RETURNED = ['rto', 'returned', 'cancelled'];

    public function forOrder(Order $order): array
    {
        $orderedTotal = (float) $order->items->sum(fn ($i) => $i->quantity * $i->sale_price);
        $shippingPool = (float) $order->shipping_charge + (float) $order->return_shipping;
        $commission = (float) $order->commission_amount;
        $fullyReturned = in_array($order->status, self::FULLY_RETURNED, true);

        $items = [];
        $totals = [
            'revenue' => 0.0, 'cogs' => 0.0, 'gross_margin' => 0.0,
            'commission' => 0.0, 'shipping' => 0.0, 'gst' => 0.0, 'input_gst' => 0.0,
            'net_profit' => 0.0, 'units_sold' => 0, 'units_returned' => 0, 'return_loss' => 0.0,
        ];

        foreach ($order->items as $item) {
            $orderedValue = $item->quantity * (float) $item->sale_price;

            $returnedQty = $fullyReturned ? $item->quantity : (int) $item->returned_quantity;
            $soldQty = max(0, $item->quantity - $returnedQty);

            $revenue = $soldQty * (float) $item->sale_price;
            $cogs = $soldQty * (float) $item->cost_price;
            $grossMargin = $revenue - $cogs;

            $shippingShare = $orderedTotal > 0 ? $shippingPool * ($orderedValue / $orderedTotal) : 0.0;
            $commissionShare = $orderedTotal > 0 ? $commission * ($revenue / $orderedTotal) : 0.0;
            $gst = $revenue * (float) $item->gst_percent / 100;        // output GST collected on sale
            $inputGst = $cogs * (float) $item->gst_percent / 100;      // input GST paid on purchase

            // Return loss: revenue forfeited on the returned units, net of recovered cost, plus the
            // slice of shipping already spent shipping out those specific returned units.
            $shippingPerUnit = $item->quantity > 0 ? $shippingShare / $item->quantity : 0.0;
            $returnLoss = ($returnedQty * (float) $item->sale_price) - ($returnedQty * (float) $item->cost_price) - ($shippingPerUnit * $returnedQty);

            $net = $grossMargin - $commissionShare - $shippingShare;

            $items[$item->id] = [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'sale_price' => (float) $item->sale_price,
                'cost_price' => (float) $item->cost_price,
                'ordered_qty' => $item->quantity,
                'returned_qty' => $returnedQty,
                'sold_qty' => $soldQty,
                'revenue' => $revenue,
                'cogs' => $cogs,
                'gross_margin' => $grossMargin,
                'commission' => $commissionShare,
                'shipping' => $shippingShare,
                'shipping_per_unit' => $shippingPerUnit,
                'gst' => $gst,
                'input_gst' => $inputGst,
                'net_profit' => $net,
                'return_loss' => $returnLoss,
            ];

            $totals['revenue'] += $revenue;
            $totals['cogs'] += $cogs;
            $totals['gross_margin'] += $grossMargin;
            $totals['commission'] += $commissionShare;
            $totals['shipping'] += $shippingShare;
            $totals['gst'] += $gst;
            $totals['input_gst'] += $inputGst;
            $totals['net_profit'] += $net;
            $totals['units_sold'] += $soldQty;
            $totals['units_returned'] += $returnedQty;
            $totals['return_loss'] += $returnLoss;
        }

        return ['items' => $items, 'totals' => $totals];
    }

    public function netProfit(Order $order): float
    {
        return $this->forOrder($order)['totals']['net_profit'];
    }

    /** Sum the per-order totals across a collection of orders (each with items loaded). */
    public function aggregate($orders): array
    {
        $sum = [
            'revenue' => 0.0, 'cogs' => 0.0, 'gross_margin' => 0.0,
            'commission' => 0.0, 'shipping' => 0.0, 'gst' => 0.0, 'input_gst' => 0.0,
            'net_profit' => 0.0, 'units_sold' => 0, 'units_returned' => 0, 'return_loss' => 0.0,
        ];

        foreach ($orders as $order) {
            foreach ($this->forOrder($order)['totals'] as $key => $value) {
                $sum[$key] += $value;
            }
        }

        return $sum;
    }

    /**
     * Aggregate profit per product across a collection of orders (each with items loaded).
     * Returns rows keyed by product label, sorted by net profit desc.
     */
    public function perProduct($orders): array
    {
        $rows = [];

        foreach ($orders as $order) {
            $breakdown = $this->forOrder($order);
            foreach ($breakdown['items'] as $line) {
                $key = $line['product_id'] ?? 'p:'.$line['product_name'];
                $rows[$key] ??= [
                    'product_id' => $line['product_id'],
                    'name' => $line['product_name'],
                    'units_sold' => 0, 'units_returned' => 0,
                    'revenue' => 0.0, 'cogs' => 0.0, 'net_profit' => 0.0,
                ];
                $rows[$key]['units_sold'] += $line['sold_qty'];
                $rows[$key]['units_returned'] += $line['returned_qty'];
                $rows[$key]['revenue'] += $line['revenue'];
                $rows[$key]['cogs'] += $line['cogs'];
                $rows[$key]['net_profit'] += $line['net_profit'];
            }
        }

        foreach ($rows as &$row) {
            $row['margin_pct'] = $row['revenue'] > 0 ? $row['net_profit'] / $row['revenue'] * 100 : 0;
        }
        unset($row);

        usort($rows, fn ($a, $b) => $b['net_profit'] <=> $a['net_profit']);

        return $rows;
    }
}
