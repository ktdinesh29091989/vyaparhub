@extends('layouts.app')

@section('title', 'Returns & Profit')
@section('heading', 'Returns & Profit')

@section('content')
    {{-- Date range --}}
    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-slate-500">From</label>
            <input type="date" name="from" value="{{ $from }}" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">To</label>
            <input type="date" name="to" value="{{ $to }}" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
        </div>
        <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Apply</button>
        <a href="{{ route('reports.profit') }}" class="px-2 py-2 text-sm text-slate-500 hover:text-slate-700">This month</a>
    </form>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        @php
            $cards = [
                ['Revenue', rupees($totals['revenue']), 'text-slate-900'],
                ['Net profit', rupees($totals['net_profit']), $totals['net_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600'],
                ['Units sold', number_format($totals['units_sold']), 'text-slate-900'],
                ['Units returned', number_format($totals['units_returned']), $totals['units_returned'] ? 'text-amber-600' : 'text-slate-500'],
            ];
        @endphp
        @foreach ($cards as $c)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500">{{ $c[0] }}</p>
                <p class="mt-1 text-xl font-bold {{ $c[2] }}">{{ $c[1] }}</p>
            </div>
        @endforeach
    </div>

    <p class="mt-4 text-sm text-slate-500">
        Profit per product for <strong>{{ \Illuminate\Support\Carbon::parse($from)->format('d M') }} – {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}</strong>
        · {{ $orderCount }} orders · {{ $returnsCount }} with returns. Commission &amp; shipping are allocated to each item.
    </p>

    {{-- Total Return Loss --}}
    <div class="mt-6 rounded-2xl border-2 border-red-200 bg-red-50 p-5">
        <p class="text-xs font-semibold text-red-700">Total Return Loss ({{ \Illuminate\Support\Carbon::parse($from)->format('d M') }} – {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }})</p>
        <p class="mt-1 text-2xl font-bold text-red-600">{{ rupees($totalReturnLoss) }}</p>
    </div>

    {{-- Returned orders list --}}
    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-3">
            <h3 class="font-semibold text-slate-900">Returned &amp; partially returned orders</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3">Channel</th>
                        <th class="px-5 py-3 text-center">Ordered Qty</th>
                        <th class="px-5 py-3 text-center">Returned Qty</th>
                        <th class="px-5 py-3 text-right">Revenue Lost</th>
                        <th class="px-5 py-3 text-right">Shipping Lost</th>
                        <th class="px-5 py-3 text-right">Net Loss</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($returnRows as $r)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-600">
                                {{ $r['order_date']->format('d M Y') }}
                                <p class="text-xs text-slate-400">{{ $r['order_number'] }}</p>
                            </td>
                            <td class="px-5 py-3 text-slate-800">{{ $r['product_name'] }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $r['channel_badge'] }}">{{ $r['channel'] }}</span>
                            </td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $r['ordered_qty'] }}</td>
                            <td class="px-5 py-3 text-center font-medium text-amber-600">{{ $r['returned_qty'] }}</td>
                            <td class="px-5 py-3 text-right text-slate-600">{{ rupees($r['revenue_lost']) }}</td>
                            <td class="px-5 py-3 text-right text-slate-600">{{ rupees($r['shipping_lost']) }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-red-600">{{ rupees($r['net_loss']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-8 text-center text-slate-400">No returns in this period. 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Per-product profitability --}}
    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3 text-center">Sold</th>
                        <th class="px-5 py-3 text-center">Returned</th>
                        <th class="px-5 py-3 text-right">Revenue</th>
                        <th class="px-5 py-3 text-right">Net profit</th>
                        <th class="px-5 py-3 text-right">Margin %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($perProduct as $p)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ $p['name'] }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $p['units_sold'] }}</td>
                            <td class="px-5 py-3 text-center {{ $p['units_returned'] ? 'text-amber-600' : 'text-slate-400' }}">{{ $p['units_returned'] }}</td>
                            <td class="px-5 py-3 text-right text-slate-600">{{ rupees($p['revenue']) }}</td>
                            <td class="px-5 py-3 text-right font-semibold {{ $p['net_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ rupees($p['net_profit']) }}</td>
                            <td class="px-5 py-3 text-right {{ $p['margin_pct'] >= 0 ? 'text-slate-600' : 'text-red-600' }}">{{ number_format($p['margin_pct'], 1) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No orders in this period yet.</td></tr>
                    @endforelse
                </tbody>
                @if (count($perProduct))
                    <tfoot class="border-t-2 border-slate-200 bg-slate-50 text-sm font-semibold">
                        <tr>
                            <td class="px-5 py-3 text-slate-700">Total</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $totals['units_sold'] }}</td>
                            <td class="px-5 py-3 text-center text-amber-600">{{ $totals['units_returned'] }}</td>
                            <td class="px-5 py-3 text-right text-slate-700">{{ rupees($totals['revenue']) }}</td>
                            <td class="px-5 py-3 text-right {{ $totals['net_profit'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ rupees($totals['net_profit']) }}</td>
                            <td class="px-5 py-3"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <p class="mt-3 text-xs text-slate-400">
        💡 Net profit = (sold units × margin) − commission − shipping. Returned units recover their goods (restocked) but the shipping they consumed is a loss. GST is treated as pass-through here; it's summarised in the P&amp;L report (Phase 5).
    </p>
@endsection
