@extends('layouts.app')

@section('title', 'Stock Health')
@section('heading', 'Stock Health')

@section('content')
@php
    $badges = [
        'top' => ['🔥 Top seller', 'bg-emerald-100 text-emerald-700'],
        'selling' => ['Selling', 'bg-blue-100 text-blue-700'],
        'slow' => ['🐢 Slow', 'bg-amber-100 text-amber-700'],
        'dead' => ['💀 Dead stock', 'bg-red-100 text-red-700'],
    ];
    $healthBadges = [
        'out' => ['🔴 Out of Stock', 'bg-red-100 text-red-700'],
        'low' => ['🟡 Low Stock', 'bg-yellow-100 text-yellow-800'],
        'healthy' => ['🟢 Healthy', 'bg-emerald-100 text-emerald-700'],
    ];
    $qs = fn ($extra) => array_merge(['window' => $window, 'dead' => $deadDays, 'filter' => $filter], $extra);
@endphp

{{-- Stock adequacy table --}}
<div class="mb-8">
    <h3 class="mb-4 font-semibold text-slate-900">Stock Health</h3>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3">SKU</th>
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3 text-center">Stock</th>
                        <th class="px-5 py-3 text-center">Threshold</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-center">Avg Daily Sales</th>
                        <th class="px-5 py-3 text-center">Days of Stock Left</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($stockHealth as $r)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ $r['name'] }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $r['sku'] ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $r['category'] ?? '—' }}</td>
                            <td class="px-5 py-3 text-center text-slate-700">{{ $r['stock'] }}</td>
                            <td class="px-5 py-3 text-center text-slate-500">{{ $r['threshold'] }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $healthBadges[$r['status']][1] }}">{{ $healthBadges[$r['status']][0] }}</span>
                            </td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ number_format($r['avg_daily_sales'], 2) }}</td>
                            <td class="px-5 py-3 text-center font-medium {{ $r['days_left'] !== null && $r['days_left'] < 7 ? 'text-red-600' : 'text-slate-700' }}">
                                {{ $r['days_left'] === null ? '∞' : number_format($r['days_left'], 1) }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                @include('products._add_stock_button', ['product' => $r['product']])
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-5 py-12 text-center text-slate-400">No products yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <p class="mt-3 text-xs text-slate-400">
        💡 <strong>Avg Daily Sales</strong> = units sold (delivered orders) in the last 30 days ÷ 30. <strong>Days of Stock Left</strong> = current stock ÷ avg daily sales.
    </p>
</div>

<h3 class="mb-4 font-semibold text-slate-900">Selling vs Dead Stock</h3>

{{-- Controls --}}
<form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
    <input type="hidden" name="filter" value="{{ $filter === 'all' ? '' : $filter }}">
    <div>
        <label class="block text-xs font-medium text-slate-500">Sales window</label>
        <select name="window" onchange="this.form.submit()" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
            @foreach ([30, 60, 90] as $w)
                <option value="{{ $w }}" @selected($window === $w)>Last {{ $w }} days</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500">Dead after no sale for</label>
        <select name="dead" onchange="this.form.submit()" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
            @foreach ([30, 45, 60, 90] as $d)
                <option value="{{ $d }}" @selected($deadDays === $d)>{{ $d }} days</option>
            @endforeach
        </select>
    </div>
</form>

{{-- Summary --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    @php
        $cards = [
            ['Active SKUs', number_format($summary['active_skus']), 'text-slate-900'],
            ['🔥 Top sellers', number_format($summary['top_count']), 'text-emerald-600'],
            ['💀 Dead stock', number_format($summary['dead_count']), $summary['dead_count'] ? 'text-red-600' : 'text-slate-500'],
            ['Capital stuck in dead stock', rupees($summary['dead_capital']), $summary['dead_capital'] ? 'text-red-600' : 'text-slate-500'],
        ];
    @endphp
    @foreach ($cards as $c)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium text-slate-500">{{ $c[0] }}</p>
            <p class="mt-1 text-xl font-bold {{ $c[2] }}">{{ $c[1] }}</p>
        </div>
    @endforeach
</div>

@if ($summary['reorder_count'] > 0 && $filter === 'all')
    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        🔁 <strong>{{ $summary['reorder_count'] }}</strong> fast-selling product(s) will run out within 2 weeks — look for the <span class="font-semibold">Reorder</span> tag below.
    </div>
@endif

{{-- Filter tabs --}}
<div class="mt-6 mb-4 flex flex-wrap gap-2 text-sm">
    @php
        $tabs = [
            'all' => 'All ('.$summary['active_skus'].')',
            'top' => '🔥 Top ('.$summary['top_count'].')',
            'selling' => 'Selling ('.$summary['selling_count'].')',
            'slow' => '🐢 Slow ('.$summary['slow_count'].')',
            'dead' => '💀 Dead ('.$summary['dead_count'].')',
        ];
    @endphp
    @foreach ($tabs as $key => $label)
        <a href="{{ route('reports.inventory', $qs(['filter' => $key === 'all' ? null : $key])) }}"
           class="rounded-full px-3 py-1.5 font-medium {{ $filter === $key ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

{{-- Table --}}
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-3">Product</th>
                    <th class="px-5 py-3 text-center">Stock</th>
                    <th class="px-5 py-3 text-center">Sold ({{ $window }}d)</th>
                    <th class="px-5 py-3 text-center">Days of cover</th>
                    <th class="px-5 py-3 text-right">Capital locked</th>
                    <th class="px-5 py-3 text-center">Last sold</th>
                    <th class="px-5 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3">
                            <a href="{{ route('products.edit', $r['id']) }}" class="font-medium text-slate-800 hover:text-brand-600">{{ $r['name'] }}</a>
                            <p class="text-xs text-slate-400">{{ $r['category'] ?? '—' }} · {{ $r['sku'] ?? 'no SKU' }}</p>
                        </td>
                        <td class="px-5 py-3 text-center text-slate-600">{{ $r['stock'] }}</td>
                        <td class="px-5 py-3 text-center font-medium text-slate-700">{{ $r['sold_window'] }}</td>
                        <td class="px-5 py-3 text-center text-slate-600">
                            {{ $r['days_cover'] === null ? '—' : $r['days_cover'].'d' }}
                            @if ($r['reorder'])
                                <span class="ml-1 rounded bg-emerald-100 px-1.5 py-0.5 text-xs font-semibold text-emerald-700">Reorder</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right {{ $r['status'] === 'dead' ? 'font-semibold text-red-600' : 'text-slate-600' }}">{{ rupees($r['capital_locked']) }}</td>
                        <td class="px-5 py-3 text-center text-slate-500">
                            {{ $r['last_sold'] ? $r['last_sold']->format('d M') : 'never' }}
                            <span class="block text-xs text-slate-400">{{ $r['days_since_sale'] }}d ago</span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $badges[$r['status']][1] }}">{{ $badges[$r['status']][0] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-slate-400">No products in this view.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<p class="mt-3 text-xs text-slate-400">
    💡 <strong>Days of cover</strong> = current stock ÷ recent daily sales rate. <strong>Dead stock</strong> = has stock but no sale in {{ $deadDays }} days — that's cash sitting on your shelf.
</p>
@endsection
