<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>P&L Report</title>
<style>
    body { font-family: sans-serif; font-size: 12px; color: #1e293b; margin: 24px; }
    h1 { font-size: 20px; margin: 0 0 2px; color: #db2777; }
    .subtitle { color: #64748b; margin-bottom: 20px; }
    .cards { width: 100%; border-collapse: separate; margin-bottom: 16px; }
    .cards td { width: 33%; padding: 10px 14px; border: 1px solid #e2e8f0; }
    .card-label { font-size: 10px; color: #64748b; text-transform: uppercase; }
    .card-value { font-size: 15px; font-weight: bold; margin-top: 2px; }
    .net-profit { text-align: center; border: 2px solid #a7f3d0; background: #ecfdf5; padding: 16px; margin-bottom: 20px; }
    .net-profit .label { font-size: 12px; color: #065f46; font-weight: bold; }
    .net-profit .value { font-size: 28px; font-weight: bold; color: #059669; margin-top: 4px; }
    table.orders { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.orders th, table.orders td { border-bottom: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; font-size: 11px; }
    table.orders th { background: #f8fafc; text-transform: uppercase; font-size: 9px; color: #64748b; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    h2 { font-size: 14px; margin: 20px 0 8px; }
</style>
</head>
<body>
    <h1>VyaparHub — Profit &amp; Loss Report</h1>
    <p class="subtitle">{{ $rangeLabel }} &middot; {{ $orderCount }} orders in range &middot; {{ $deliveredOrders->count() }} delivered</p>

    <table class="cards">
        <tr>
            <td><div class="card-label">Total Revenue</div><div class="card-value">{{ rupees($pnl['revenue']) }}</div></td>
            <td><div class="card-label">Total Cost</div><div class="card-value">{{ rupees($pnl['cogs']) }}</div></td>
            <td><div class="card-label">Gross Profit</div><div class="card-value">{{ rupees($pnl['gross_profit']) }}</div></td>
        </tr>
        <tr>
            <td><div class="card-label">Total Shipping Spent</div><div class="card-value">{{ rupees($pnl['shipping']) }}</div></td>
            <td><div class="card-label">Total Commission/Fees</div><div class="card-value">{{ rupees($pnl['commission']) }}</div></td>
            <td><div class="card-label">Total Return Loss</div><div class="card-value">{{ rupees($pnl['return_loss']) }}</div></td>
        </tr>
    </table>

    <div class="net-profit">
        <div class="label">NET PROFIT</div>
        <div class="value">{{ rupees($pnl['net_profit']) }}</div>
    </div>

    <h2>Delivered orders</h2>
    <table class="orders">
        <thead>
            <tr>
                <th>Date</th><th>Product</th><th>Channel</th>
                <th class="text-center">Qty</th><th class="text-right">Revenue</th><th class="text-right">Cost</th><th class="text-right">Profit</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($deliveredOrders as $order)
                @php
                    $revenue = $order->items->sum(fn ($i) => $i->quantity * $i->sale_price);
                    $cost = $order->items->sum(fn ($i) => $i->quantity * $i->cost_price);
                    $qty = $order->items->sum('quantity');
                    $label = $order->items->first()?->product_name . ($order->items->count() > 1 ? ' +'.($order->items->count() - 1).' more' : '');
                @endphp
                <tr>
                    <td>{{ $order->order_date->format('d M Y') }}</td>
                    <td>{{ $label }}</td>
                    <td>{{ $order->channel->name ?? '—' }}</td>
                    <td class="text-center">{{ $qty }}</td>
                    <td class="text-right">{{ rupees($revenue) }}</td>
                    <td class="text-right">{{ rupees($cost) }}</td>
                    <td class="text-right">{{ rupees($revenue - $cost) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">No delivered orders in this range.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
