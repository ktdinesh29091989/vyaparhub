@extends('layouts.app')

@section('title', 'Order '.$order->order_number)
@section('heading', 'Order '.$order->order_number)
@section('back', route('orders.index'))

@section('content')
@php $t = $breakdown['totals']; @endphp

@if ($errors->any())
    <div class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
        @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
    </div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Items with per-item profit --}}
    <div class="space-y-6 lg:col-span-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-slate-900">Items &amp; profit per piece</h3>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $order->status_color }}">{{ $order->status_label }}</span>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="py-2">Product</th>
                            <th class="py-2 text-center">Sold / Ret.</th>
                            <th class="py-2 text-right">Revenue</th>
                            <th class="py-2 text-right">Cost</th>
                            <th class="py-2 text-right">Comm.+Ship</th>
                            <th class="py-2 text-right">Net profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($order->items as $item)
                            @php $b = $breakdown['items'][$item->id]; @endphp
                            <tr>
                                <td class="py-2.5">
                                    <p class="font-medium text-slate-800">{{ $item->product_name }}</p>
                                    <p class="text-xs text-slate-400">{{ $item->quantity }} × {{ rupees($item->sale_price) }}</p>
                                </td>
                                <td class="py-2.5 text-center">
                                    <span class="text-emerald-600">{{ $b['sold_qty'] }}</span>
                                    <span class="text-slate-300">/</span>
                                    <span class="{{ $b['returned_qty'] ? 'text-red-600' : 'text-slate-400' }}">{{ $b['returned_qty'] }}</span>
                                </td>
                                <td class="py-2.5 text-right text-slate-600">{{ rupees($b['revenue']) }}</td>
                                <td class="py-2.5 text-right text-slate-400">{{ rupees($b['cogs']) }}</td>
                                <td class="py-2.5 text-right text-slate-400">{{ rupees($b['commission'] + $b['shipping']) }}</td>
                                <td class="py-2.5 text-right font-semibold {{ $b['net_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ rupees($b['net_profit']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Record return --}}
        @if ($canReturn)
            <div class="rounded-2xl border border-orange-200 bg-orange-50/50 p-6 shadow-sm" x-data="{ open: false }">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">↩️ Record a return / RTO</h3>
                    <button @click="open = !open" class="rounded-lg border border-orange-300 px-3 py-1.5 text-sm font-semibold text-orange-700 hover:bg-orange-100" x-text="open ? 'Close' : 'Record return'"></button>
                </div>
                <form x-show="open" x-cloak method="POST" action="{{ route('orders.return', $order) }}" class="mt-4 space-y-4">
                    @csrf
                    <p class="text-sm text-slate-500">Enter how many units came back for each product. Stock is restored automatically.</p>
                    <div class="space-y-2">
                        @foreach ($order->items as $item)
                            @if ($item->returnable_quantity > 0)
                                <div class="flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2">
                                    <span class="text-sm text-slate-700">{{ $item->product_name }} <span class="text-xs text-slate-400">({{ $item->returnable_quantity }} returnable)</span></span>
                                    <input type="number" min="0" max="{{ $item->returnable_quantity }}" name="returns[{{ $item->id }}]" value="0"
                                           class="w-20 rounded-lg border border-slate-300 px-2 py-1.5 text-center text-sm focus:border-orange-500 focus:outline-none">
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Return type</label>
                            <select name="type" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-orange-500 focus:outline-none">
                                <option value="returned">Customer return</option>
                                <option value="rto">RTO (never delivered)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Return shipping ₹</label>
                            <input type="number" step="0.01" min="0" name="return_shipping" value="0"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-orange-500 focus:outline-none">
                        </div>
                    </div>
                    <button class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Save return</button>
                </form>
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-3 font-semibold text-slate-900">Customer &amp; channel</h3>
            <dl class="grid grid-cols-2 gap-y-3 text-sm">
                <dt class="text-slate-500">Customer</dt><dd class="text-slate-800">{{ $order->customer_name ?? '—' }}</dd>
                <dt class="text-slate-500">Phone</dt><dd class="text-slate-800">{{ $order->customer_phone ?? '—' }}</dd>
                <dt class="text-slate-500">Channel</dt><dd class="text-slate-800">{{ $order->channel->name ?? '—' }}</dd>
                <dt class="text-slate-500">Order date</dt><dd class="text-slate-800">{{ $order->order_date->format('d M Y') }}</dd>
                <dt class="text-slate-500">Source</dt><dd class="text-slate-800 capitalize">{{ $order->source }}</dd>
            </dl>
            @if ($order->notes)
                <p class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">📝 {{ $order->notes }}</p>
            @endif
        </div>
    </div>

    {{-- Profit summary + actions --}}
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-semibold text-slate-900">Order profit</h3>
            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Revenue (sold)</span><span class="font-medium">{{ rupees($t['revenue']) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">− Cost of goods</span><span>{{ rupees($t['cogs']) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">− Commission</span><span>{{ rupees($t['commission']) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">− Shipping{{ $order->return_shipping > 0 ? ' (incl. return)' : '' }}</span><span>{{ rupees($t['shipping']) }}</span></div>
                <div class="flex justify-between border-t border-slate-100 pt-3 text-base">
                    <span class="font-semibold text-slate-800">Net profit</span>
                    <span class="font-bold {{ $t['net_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ rupees($t['net_profit']) }}</span>
                </div>
                <div class="flex justify-between pt-1 text-xs text-slate-400">
                    <span>GST collected (pass-through)</span><span>{{ rupees($t['gst']) }}</span>
                </div>
            </div>
            @if ($t['units_returned'] > 0)
                <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                    {{ $t['units_returned'] }} unit(s) returned — goods restocked, commission refunded; shipping is still a loss.
                </p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-3 font-semibold text-slate-900">Update status</h3>
            @if ($order->status === 'delivered')
                <p class="rounded-lg bg-emerald-50 px-3 py-2.5 text-sm font-semibold text-emerald-700">✅ Delivered — locked, no further status changes.</p>
            @elseif (in_array($order->status, ['returned', 'rto']))
                <p class="rounded-lg bg-red-50 px-3 py-2.5 text-sm font-semibold text-red-700">🔒 {{ $order->status_label }} — locked, no further status changes.</p>
            @else
                <form method="POST" action="{{ route('orders.status', $order) }}" class="flex gap-2">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm capitalize focus:border-brand-500 focus:outline-none">
                        @foreach ($manualStatuses as $s)
                            <option value="{{ $s }}" @selected($order->status === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Save</button>
                </form>
                <p class="mt-2 text-xs text-slate-400">Returns are recorded above. Cancelling restocks all outstanding units.</p>
            @endif
        </div>

        <form method="POST" action="{{ route('orders.destroy', $order) }}" onsubmit="return confirm('Delete this order? Outstanding stock will be restored.')">
            @csrf
            @method('DELETE')
            <button class="w-full rounded-lg border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50">Delete order</button>
        </form>
    </div>
</div>

<div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="mb-4 font-semibold text-slate-900">Stock movements for this order</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="py-2 pr-4">Date</th>
                    <th class="py-2 pr-4">Product</th>
                    <th class="py-2 pr-4">Type</th>
                    <th class="py-2 pr-4 text-right">Qty</th>
                    <th class="py-2">Note</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($stockHistory as $m)
                    <tr>
                        <td class="py-2.5 pr-4 text-slate-600">{{ $m->created_at->format('d M Y, h:i A') }}</td>
                        <td class="py-2.5 pr-4 text-slate-800">{{ $m->product->name ?? '—' }}</td>
                        <td class="py-2.5 pr-4">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $m->type === 'return' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ ucfirst($m->type) }}
                            </span>
                        </td>
                        <td class="py-2.5 pr-4 text-right font-semibold {{ $m->quantity >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $m->quantity >= 0 ? '+' : '' }}{{ $m->quantity }}
                        </td>
                        <td class="py-2.5 text-slate-500">{{ $m->note ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-slate-400">No stock movements recorded for this order.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
