@extends('layouts.app')

@section('title', 'Orders')
@section('heading', 'Orders')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="grid flex-1 grid-cols-2 gap-3 sm:grid-cols-4">
            @php
                $tiles = [
                    ['All orders', $summary['total'], 'text-slate-900', null],
                    ['Open', $summary['open'], 'text-blue-600', 'placed'],
                    ['Delivered', $summary['delivered'], 'text-emerald-600', 'delivered'],
                    ['Returns / RTO', $summary['returns'], 'text-amber-600', 'rto'],
                ];
            @endphp
            @foreach ($tiles as $t)
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">{{ $t[0] }}</p>
                    <p class="mt-0.5 text-xl font-bold {{ $t[2] }}">{{ number_format($t[1]) }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('orders.create') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">+ New order</a>
        <a href="{{ route('orders.quick') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">💬 WhatsApp quick entry</a>
        <a href="{{ route('orders.import.form') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">⬆️ Import CSV</a>
    </div>

    {{-- Status pill filter --}}
    @php
        $pillLabels = ['' => 'All', 'placed' => 'Placed', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'returns' => 'Returned', 'cancelled' => 'Cancelled'];
    @endphp
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ($pillLabels as $value => $label)
            <a href="{{ route('orders.index', ['status' => $value] + array_filter(request()->except('status'))) }}"
               class="rounded-full px-3.5 py-1.5 text-sm font-medium {{ (request('status', '') === $value) ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <div>
            <label class="block text-xs font-medium text-slate-500">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Order #, product, customer, phone…"
                   class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Channel</label>
            <select name="channel" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
                <option value="">All channels</option>
                @foreach ($channels as $c)
                    <option value="{{ $c->id }}" @selected(request('channel') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Category</label>
            <select name="category" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
                <option value="">All categories</option>
                @foreach ($categories as $slug => $label)
                    <option value="{{ $slug }}" @selected(request('category') === $slug)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">From</label>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">To</label>
            <input type="date" name="to" value="{{ request('to') }}"
                   class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
        </div>
        <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Filter</button>
        @if(request()->hasAny(['search','channel','category','from','to']) || request('status'))
            <a href="{{ route('orders.index') }}" class="px-2 py-2 text-sm text-slate-500 hover:text-slate-700">Clear</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3">Channel</th>
                        <th class="px-5 py-3 text-center">Qty</th>
                        <th class="px-5 py-3 text-right">Revenue</th>
                        <th class="px-5 py-3 text-right">Profit</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $order)
                        @php
                            $firstItem = $order->items->first();
                            $productLabel = $firstItem?->product_name . ($order->items_count > 1 ? ' +' . ($order->items_count - 1) . ' more' : '');
                            $totalQty = $order->items->sum('quantity');
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 cursor-pointer" onclick="window.location='{{ route('orders.show', $order) }}'">
                                <p class="font-medium text-slate-800">{{ $order->order_number }}</p>
                                <p class="text-xs text-slate-400">{{ $order->order_date->format('d M Y') }}</p>
                            </td>
                            <td class="px-5 py-3 cursor-pointer text-slate-600" onclick="window.location='{{ route('orders.show', $order) }}'">
                                {{ $productLabel ?? '—' }}
                                @if ($order->customer_name)
                                    <p class="text-xs text-slate-400">{{ $order->customer_name }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $order->channel->badge_color ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $order->channel->name ?? '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $totalQty }}</td>
                            <td class="px-5 py-3 text-right font-medium text-slate-700">{{ rupees($order->subtotal) }}</td>
                            <td class="px-5 py-3 text-right font-semibold {{ $order->net_profit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ rupees($order->net_profit) }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $order->status_color }}">{{ $order->status_label }}</span>
                            </td>
                            <td class="px-5 py-3 text-right" onclick="event.stopPropagation()">
                                @if ($order->status === 'delivered')
                                    <span class="text-xs font-semibold text-emerald-600">✅ Delivered</span>
                                @elseif (in_array($order->status, ['returned', 'rto']))
                                    <span class="text-xs font-semibold text-red-600">🔒 {{ $order->status_label }}</span>
                                @else
                                    <form method="POST" action="{{ route('orders.status', $order) }}" class="flex items-center justify-end gap-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="rounded-lg border border-slate-300 px-2 py-1 text-xs capitalize focus:border-brand-500 focus:outline-none">
                                            @foreach ($manualStatuses as $s)
                                                <option value="{{ $s }}" @selected($order->status === $s)>{{ $s }}</option>
                                            @endforeach
                                        </select>
                                        <button class="rounded-lg bg-slate-900 px-2 py-1 text-xs font-semibold text-white hover:bg-slate-800">Save</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center">
                                <p class="text-slate-400">No orders yet.</p>
                                <div class="mt-3 flex justify-center gap-2">
                                    <a href="{{ route('orders.create') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">+ New order</a>
                                    <a href="{{ route('orders.import.form') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Import CSV</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($orders->isNotEmpty())
                    <tfoot class="border-t-2 border-slate-200 bg-slate-50 text-sm font-semibold">
                        <tr>
                            <td class="px-5 py-3 text-slate-600" colspan="3">Totals ({{ $filtered['count'] }} order{{ $filtered['count'] === 1 ? '' : 's' }} shown)</td>
                            <td class="px-5 py-3 text-center text-slate-400">—</td>
                            <td class="px-5 py-3 text-right text-slate-800">{{ rupees($filtered['revenue']) }}</td>
                            <td class="px-5 py-3 text-right {{ $filtered['net_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ rupees($filtered['net_profit']) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>

    <p class="mt-3 text-xs text-slate-400">
        💡 Net profit = sale − cost − commission − shipping. Returns/RTO show the shipping loss.
    </p>
@endsection
