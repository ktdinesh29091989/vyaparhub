@extends('layouts.app')

@section('title', 'P&L Report')
@section('heading', 'P&L Report')

@section('content')
@unless (auth()->user()->isPro())
    {{-- Free plan: basic summary only — today + this week, no date-range report or table. --}}
    <div class="mx-auto max-w-2xl">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Basic P&amp;L summary — Free plan</p>
            <div class="mt-5 grid grid-cols-2 gap-4">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-medium text-slate-500">Today's Net Profit</p>
                    <p class="mt-1 text-2xl font-bold {{ $todayPnl['net_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ rupees($todayPnl['net_profit']) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Revenue {{ rupees($todayPnl['revenue']) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-medium text-slate-500">This Week's Net Profit</p>
                    <p class="mt-1 text-2xl font-bold {{ $weekPnl['net_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ rupees($weekPnl['net_profit']) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Revenue {{ rupees($weekPnl['revenue']) }}</p>
                </div>
            </div>
            <p class="mt-5 text-sm text-slate-500">Upgrade to Pro for full date-range reports, delivered-orders table, GST summary and PDF export.</p>
            <a href="{{ route('upgrade') }}" class="mt-4 inline-block rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Upgrade to Pro</a>
        </div>
    </div>
@else
@php
    $deltaPct = fn ($now, $prev) => $prev != 0 ? ($now - $prev) / abs($prev) * 100 : ($now > 0 ? 100 : 0);
    $netDelta = $deltaPct($pnl['net_profit'], $prevNet);
    $revDelta = $deltaPct($pnl['revenue'], $prevRevenue);
@endphp

{{-- Controls --}}
<div class="mb-6 flex flex-wrap items-end justify-between gap-3 print:hidden">
    <form method="GET" class="flex flex-wrap items-end gap-2">
        <div>
            <label class="block text-xs font-medium text-slate-500">From</label>
            <input type="date" name="from" value="{{ $from }}" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">To</label>
            <input type="date" name="to" value="{{ $to }}" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
        </div>
        <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Apply</button>
        <a href="{{ route('reports.pnl') }}" class="px-2 py-2 text-sm text-slate-500 hover:text-slate-700">This month</a>
    </form>
    <div class="flex gap-2">
        <a href="{{ route('reports.pnl.pdf', ['from' => $from, 'to' => $to]) }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">⬇️ Download PDF</a>
        <a href="{{ route('reports.pnl.export', ['from' => $from, 'to' => $to]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">⬇️ Export CSV</a>
        <button onclick="window.print()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">🖨️ Print</button>
    </div>
</div>

<div class="mb-1 text-sm text-slate-500">{{ $rangeLabel }} · {{ $orderCount }} orders in range · {{ $deliveredOrders->count() }} delivered</div>

{{-- Summary cards --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
    @php
        $cards = [
            ['Total Revenue', rupees($pnl['revenue']), 'text-slate-900'],
            ['Total Cost', rupees($pnl['cogs']), 'text-slate-900'],
            ['Gross Profit', rupees($pnl['gross_profit']), 'text-slate-900'],
            ['Total Shipping Spent', rupees($pnl['shipping']), 'text-slate-900'],
            ['Total Commission/Fees', rupees($pnl['commission']), 'text-slate-900'],
            ['Total Return Loss', rupees($pnl['return_loss']), $pnl['return_loss'] > 0 ? 'text-red-600' : 'text-slate-900'],
        ];
    @endphp
    @foreach ($cards as $c)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium text-slate-500">{{ $c[0] }}</p>
            <p class="mt-1 text-xl font-bold {{ $c[2] }}">{{ $c[1] }}</p>
        </div>
    @endforeach
</div>

{{-- NET PROFIT — the headline number --}}
<div class="mt-4 rounded-2xl border-2 border-emerald-200 bg-emerald-50 p-6 text-center shadow-sm">
    <p class="text-sm font-semibold text-emerald-800">NET PROFIT</p>
    <p class="mt-1 text-4xl font-extrabold {{ $pnl['net_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ rupees($pnl['net_profit']) }}</p>
    <p class="mt-1 text-xs font-medium {{ $netDelta >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
        {{ $netDelta >= 0 ? '▲' : '▼' }} {{ number_format(abs($netDelta), 1) }}% vs previous period · margin {{ number_format($pnl['margin_pct'], 1) }}%
    </p>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- P&L statement --}}
    <div class="lg:col-span-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-semibold text-slate-900">Profit &amp; Loss — {{ $rangeLabel }}</h3>
            <table class="min-w-full text-sm">
                <tbody>
                    @php
                        $line = function ($label, $amount, $opts = []) {
                            $bold = $opts['bold'] ?? false; $sub = $opts['sub'] ?? false; $top = $opts['top'] ?? false;
                            $color = $amount < 0 ? 'text-red-600' : 'text-slate-700';
                            $cls = ($bold ? 'font-bold text-slate-900 ' : '').($sub ? 'pl-6 text-slate-500 ' : '');
                            $border = $top ? 'border-t-2 border-slate-200' : 'border-t border-slate-100';
                            return [$label, $amount, $cls, $border, $bold ? 'text-slate-900' : $color];
                        };
                        $rows = [
                            $line('Total Revenue (delivered)', $pnl['revenue'], ['bold' => true]),
                            $line('Total Cost', -$pnl['cogs'], ['sub' => true]),
                            $line('Gross Profit', $pnl['gross_profit'], ['bold' => true, 'top' => true]),
                            $line('Total Commission/Fees', -$pnl['commission'], ['sub' => true]),
                            $line('Total Shipping Spent', -$pnl['shipping'], ['sub' => true]),
                            $line('Total Return Loss', -$pnl['return_loss'], ['sub' => true]),
                            $line('Other expenses', -$pnl['expenses'], ['sub' => true]),
                            $line('NET PROFIT', $pnl['net_profit'], ['bold' => true, 'top' => true]),
                        ];
                    @endphp
                    @foreach ($rows as [$label, $amount, $cls, $border, $color])
                        <tr class="{{ $border }}">
                            <td class="py-2.5 {{ $cls }}">{{ $label }}</td>
                            <td class="py-2.5 text-right {{ $color }} {{ str_contains($cls, 'font-bold') ? 'font-bold' : '' }}">
                                {{ rupees($amount) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="mt-3 text-xs text-slate-400">
                Revenue/Cost/Gross Profit/Shipping/Fees are computed on <strong>delivered</strong> orders only.
                Return Loss is computed separately from orders that came back in this period.
                "Other expenses" (below) further reduces net profit but isn't part of your requested Gross-Profit formula.
            </p>
        </div>

        {{-- Delivered orders table --}}
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-semibold text-slate-900">Delivered orders — {{ $rangeLabel }}</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="py-2">Date</th>
                            <th class="py-2">Product</th>
                            <th class="py-2">Channel</th>
                            <th class="py-2 text-center">Qty</th>
                            <th class="py-2 text-right">Revenue</th>
                            <th class="py-2 text-right">Cost</th>
                            <th class="py-2 text-right">Profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($deliveredOrders as $order)
                            @php
                                $revenue = $order->items->sum(fn ($i) => $i->quantity * $i->sale_price);
                                $cost = $order->items->sum(fn ($i) => $i->quantity * $i->cost_price);
                                $qty = $order->items->sum('quantity');
                                $label = $order->items->first()?->product_name . ($order->items->count() > 1 ? ' +'.($order->items->count() - 1).' more' : '');
                            @endphp
                            <tr>
                                <td class="py-2.5 text-slate-600">{{ $order->order_date->format('d M Y') }}</td>
                                <td class="py-2.5 text-slate-800">{{ $label }}</td>
                                <td class="py-2.5">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $order->channel->badge_color ?? 'bg-slate-100 text-slate-600' }}">{{ $order->channel->name ?? '—' }}</span>
                                </td>
                                <td class="py-2.5 text-center text-slate-600">{{ $qty }}</td>
                                <td class="py-2.5 text-right text-slate-600">{{ rupees($revenue) }}</td>
                                <td class="py-2.5 text-right text-slate-400">{{ rupees($cost) }}</td>
                                <td class="py-2.5 text-right font-semibold {{ ($revenue - $cost) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ rupees($revenue - $cost) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-6 text-center text-slate-400">No delivered orders in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Channel breakdown --}}
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-1 font-semibold text-slate-900">By sales channel</h3>
            <p class="mb-3 text-xs text-slate-400">All order activity in range (not delivered-only).</p>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr><th class="py-2">Channel</th><th class="py-2 text-center">Orders</th><th class="py-2 text-right">Revenue</th><th class="py-2 text-right">Net profit</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($channels as $c)
                        <tr>
                            <td class="py-2.5 font-medium text-slate-800">{{ $c['name'] }}</td>
                            <td class="py-2.5 text-center text-slate-600">{{ $c['orders'] }}</td>
                            <td class="py-2.5 text-right text-slate-600">{{ rupees($c['revenue']) }}</td>
                            <td class="py-2.5 text-right font-semibold {{ $c['net_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ rupees($c['net_profit']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-slate-400">No orders in this range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Right column: chart + GST --}}
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-semibold text-slate-900">Where the revenue goes</h3>
            @if ($pnl['revenue'] > 0)
                <canvas id="costChart" height="220"></canvas>
            @else
                <p class="py-8 text-center text-sm text-slate-400">No delivered revenue in this range yet.</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-semibold text-slate-900">GST summary</h3>
            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Output GST (on sales)</span><span class="font-medium">{{ rupees($gst['output']) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">− Input GST (on purchases)</span><span>{{ rupees($gst['input']) }}</span></div>
                <div class="flex justify-between border-t border-slate-100 pt-2.5 text-base">
                    <span class="font-semibold text-slate-800">Net GST payable</span>
                    <span class="font-bold {{ $gst['net'] >= 0 ? 'text-slate-900' : 'text-emerald-600' }}">{{ rupees($gst['net']) }}</span>
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-400">Estimate based on each product's GST %. Confirm with your CA before filing.</p>
        </div>
    </div>
</div>

{{-- Expenses ledger --}}
<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3 print:hidden">
    <div class="lg:col-span-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-semibold text-slate-900">Expenses — {{ $rangeLabel }}</h3>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr><th class="py-2">Date</th><th class="py-2">Category</th><th class="py-2">Description</th><th class="py-2 text-right">Amount</th><th></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($expenses as $e)
                        <tr>
                            <td class="py-2.5 text-slate-600">{{ $e->spent_on->format('d M') }}</td>
                            <td class="py-2.5 text-slate-700">{{ $e->category }}</td>
                            <td class="py-2.5 text-slate-500">{{ $e->description ?? '—' }}</td>
                            <td class="py-2.5 text-right font-medium text-slate-700">{{ rupees($e->amount) }}</td>
                            <td class="py-2.5 text-right">
                                <form method="POST" action="{{ route('expenses.destroy', $e) }}" onsubmit="return confirm('Remove this expense?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-400 hover:text-red-600">✕</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-400">No expenses logged in this range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 font-semibold text-slate-900">Add expense</h3>
        @if ($errors->any())
            <div class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">@foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
        @endif
        <form method="POST" action="{{ route('expenses.store') }}" class="space-y-3">
            @csrf
            <input type="date" name="spent_on" value="{{ now()->toDateString() }}" required class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
            <select name="category" required class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
                @foreach ($categories as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
            </select>
            <input name="description" placeholder="Description (optional)" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
            <input type="number" step="0.01" min="0.01" name="amount" placeholder="Amount ₹" required class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
            <button class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Add expense</button>
        </form>
    </div>
</div>

@if ($pnl['revenue'] > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    new Chart(document.getElementById('costChart'), {
        type: 'doughnut',
        data: {
            labels: ['Cost of goods', 'Commission/Fees', 'Shipping', 'Return loss', 'Expenses', 'Net profit'],
            datasets: [{
                data: [
                    {{ round($pnl['cogs']) }}, {{ round($pnl['commission']) }}, {{ round($pnl['shipping']) }},
                    {{ round(max(0, $pnl['return_loss'])) }}, {{ round($pnl['expenses']) }}, {{ max(0, round($pnl['net_profit'])) }}
                ],
                backgroundColor: ['#94a3b8', '#fbbf24', '#60a5fa', '#ef4444', '#f472b6', '#34d399'],
                borderWidth: 0,
            }],
        },
        options: {
            cutout: '62%',
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { size: 11 } } } },
        },
    });
</script>
@endif
@endunless
@endsection
