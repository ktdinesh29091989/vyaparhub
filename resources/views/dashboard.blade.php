@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Namaste, {{ auth()->user()->name }} 👋</h2>
        <p class="mt-1 text-sm text-slate-500">Here's your live inventory snapshot.</p>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-5">
        @php
            $summaryCards = [
                ['💰', "Today's Revenue", rupees($cards['todays_revenue']), 'text-emerald-600'],
                ['📈', "Today's Profit", rupees($cards['todays_profit']), $cards['todays_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600'],
                ['🧾', "This Month's Orders", number_format($cards['month_orders']), 'text-slate-900'],
                ['⚠️', 'Low Stock Products', number_format($cards['low_stock_products']), $cards['low_stock_products'] ? 'text-amber-600' : 'text-slate-500'],
                ['⏳', 'Pending Actions', number_format($cards['pending_actions']), $cards['pending_actions'] ? 'text-blue-600' : 'text-slate-500'],
            ];
        @endphp
        @foreach ($summaryCards as $c)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-xl">{{ $c[0] }}</span>
                <p class="mt-4 text-sm font-medium text-slate-500">{{ $c[1] }}</p>
                <p class="mt-1 text-2xl font-bold {{ $c[3] }}">{{ $c[2] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        @if (auth()->user()->isPro())
        {{-- 7-day revenue chart --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h3 class="font-semibold text-slate-900">Revenue — last 7 days</h3>
            <canvas id="revenueChart" height="220" class="mt-4"></canvas>
        </div>
        @endif

        {{-- Quick actions --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm {{ auth()->user()->isPro() ? '' : 'lg:col-span-3' }}">
            <h3 class="font-semibold text-slate-900">Quick actions</h3>
            <div class="mt-4 space-y-3">
                <a href="{{ route('orders.create') }}" class="block rounded-lg bg-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-700">+ New order</a>
                <a href="{{ route('orders.quick') }}" class="block rounded-lg bg-emerald-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-emerald-700">💬 WhatsApp quick entry</a>
                <a href="{{ route('products.create') }}" class="block rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">+ Add product</a>
                <a href="{{ route('orders.import.form') }}" class="block rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">⬆️ Import orders CSV</a>
            </div>
        </div>
    </div>

    {{-- Low stock alert list --}}
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-900">⚠️ Low stock alerts</h3>
            <a href="{{ route('products.index', ['filter' => 'low']) }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View all</a>
        </div>
        @if ($lowStock->isNotEmpty())
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="py-2">Product</th>
                            <th class="py-2">SKU</th>
                            <th class="py-2 text-center">Current Stock</th>
                            <th class="py-2 text-center">Threshold</th>
                            <th class="py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($lowStock as $p)
                            <tr>
                                <td class="py-2.5 font-medium text-slate-800">{{ $p->name }}</td>
                                <td class="py-2.5 text-slate-500">{{ $p->sku ?? '—' }}</td>
                                <td class="py-2.5 text-center">
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $p->stock }}</span>
                                </td>
                                <td class="py-2.5 text-center text-slate-500">{{ $p->stock_threshold }}</td>
                                <td class="py-2.5 text-right">
                                    @include('products._add_stock_button', ['product' => $p])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="mt-4 text-sm text-slate-400">🎉 Nothing low on stock. You're all set.</p>
        @endif
    </div>

    {{-- Recent orders --}}
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-900">🧾 Recent orders</h3>
            <a href="{{ route('orders.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View all</a>
        </div>
        @forelse ($recentOrders as $order)
            <a href="{{ route('orders.show', $order) }}" class="flex items-center justify-between border-t border-slate-100 py-3 first:border-0 hover:bg-slate-50">
                <div>
                    <p class="text-sm font-medium text-slate-800">{{ $order->order_number }} <span class="text-xs text-slate-400">· {{ $order->channel->name ?? '—' }}</span></p>
                    <p class="text-xs text-slate-400">{{ $order->customer_name ?? 'No name' }} · {{ $order->order_date->format('d M') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-slate-700">{{ rupees($order->subtotal) }}</span>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $order->status_color }}">{{ $order->status_label }}</span>
                </div>
            </a>
        @empty
            <p class="mt-4 text-sm text-slate-400">No orders yet. <a href="{{ route('orders.create') }}" class="font-medium text-brand-600">Create your first order →</a></p>
        @endforelse
    </div>

    @if (auth()->user()->isPro())
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($chart['labels']) !!},
                datasets: [{
                    label: 'Revenue',
                    data: {!! json_encode($chart['data']) !!},
                    backgroundColor: '#E91E8C',
                    borderRadius: 6,
                }],
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: (v) => '₹' + v } } },
            },
        });
    </script>
    @endif
@endsection
