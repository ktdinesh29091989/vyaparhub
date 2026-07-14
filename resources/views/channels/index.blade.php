@extends('layouts.app')

@section('title', 'Sales Channels')
@section('heading', 'Sales Channels')

@section('content')
    {{-- Performance breakdown --}}
    <div class="mb-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <h3 class="font-semibold text-slate-900">Channel performance</h3>
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
                <a href="{{ route('channels.index') }}" class="px-2 py-2 text-sm text-slate-500 hover:text-slate-700">This month</a>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Channel</th>
                                <th class="px-5 py-3 text-center">Orders</th>
                                <th class="px-5 py-3 text-right">Revenue</th>
                                <th class="px-5 py-3 text-right">Profit</th>
                                <th class="px-5 py-3 text-right">Avg Profit/Order</th>
                                <th class="px-5 py-3 text-right">Return Rate</th>
                                <th class="px-5 py-3">Best Seller</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($breakdown as $b)
                                <tr>
                                    <td class="px-5 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $b['badge_color'] }}">{{ $b['name'] }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-center text-slate-600">{{ $b['orders'] }}</td>
                                    <td class="px-5 py-3 text-right text-slate-600">{{ rupees($b['revenue']) }}</td>
                                    <td class="px-5 py-3 text-right font-semibold {{ $b['profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ rupees($b['profit']) }}</td>
                                    <td class="px-5 py-3 text-right text-slate-600">{{ rupees($b['avg_profit']) }}</td>
                                    <td class="px-5 py-3 text-right {{ $b['return_rate'] > 10 ? 'text-red-600 font-medium' : 'text-slate-600' }}">{{ number_format($b['return_rate'], 1) }}%</td>
                                    <td class="px-5 py-3 text-slate-600">{{ $b['best_seller'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-5 py-8 text-center text-slate-400">No orders in this range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 font-semibold text-slate-900">Profit by channel</h3>
                @if (collect($breakdown)->sum('profit') != 0 || collect($breakdown)->sum('orders') > 0)
                    <canvas id="channelChart" height="220"></canvas>
                @else
                    <p class="py-8 text-center text-sm text-slate-400">No orders in this range yet.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="mb-6 max-w-2xl">
        <p class="text-sm text-slate-500">
            Set the commission % and default shipping each marketplace charges you. These rates power
            the <strong>profit-per-item</strong> and <strong>P&amp;L</strong> calculations.
        </p>
    </div>

    <form method="POST" action="{{ route('channels.update') }}" class="max-w-3xl">
        @csrf
        @method('PUT')

        <div class="space-y-4">
            @foreach ($channels as $channel)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-lg">
                                {{ ['meesho' => '🛍️', 'amazon' => '📦', 'whatsapp' => '💬', 'local' => '🏬'][$channel->slug] ?? '🏷️' }}
                            </span>
                            <h3 class="font-semibold text-slate-900">{{ $channel->name }}</h3>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="hidden" name="channels[{{ $channel->id }}][is_active]" value="0">
                            <input type="checkbox" name="channels[{{ $channel->id }}][is_active]" value="1" @checked($channel->is_active)
                                   class="rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                            Active
                        </label>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Commission %</label>
                            <input type="number" step="0.01" min="0" max="100"
                                   name="channels[{{ $channel->id }}][commission_percent]" value="{{ $channel->commission_percent }}"
                                   class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Default shipping (₹)</label>
                            <input type="number" step="0.01" min="0"
                                   name="channels[{{ $channel->id }}][shipping_charge]" value="{{ $channel->shipping_charge }}"
                                   class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <button class="mt-6 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Save settings</button>
    </form>

    {{-- Add a custom channel --}}
    <div class="mt-8 max-w-3xl rounded-2xl border border-dashed border-slate-300 bg-white p-5 sm:p-6">
        <h3 class="font-semibold text-slate-900">Sell somewhere else too?</h3>
        <p class="mt-1 text-sm text-slate-500">Add any other channel — Flipkart, JioMart, your own website — and set its commission &amp; shipping just like the built-in ones.</p>
        <form method="POST" action="{{ route('channels.store') }}" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4">
            @csrf
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Channel name</label>
                <input name="name" value="{{ old('name') }}" required placeholder="e.g. Flipkart"
                       class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none @error('name') border-red-400 @enderror">
                @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Commission %</label>
                <input type="number" step="0.01" min="0" max="100" name="commission_percent" value="{{ old('commission_percent', 0) }}"
                       class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Default shipping (₹)</label>
                <input type="number" step="0.01" min="0" name="shipping_charge" value="{{ old('shipping_charge', 0) }}"
                       class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
            </div>
            <div class="sm:col-span-4">
                <button class="rounded-lg border border-brand-600 px-5 py-2.5 text-sm font-semibold text-brand-600 hover:bg-brand-50">+ Add channel</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        new Chart(document.getElementById('channelChart'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_column($breakdown, 'name')) !!},
                datasets: [{
                    data: {!! json_encode(array_map(fn ($b) => max(0, round($b['profit'])), $breakdown)) !!},
                    backgroundColor: {!! json_encode(array_column($breakdown, 'chart_color')) !!},
                    borderWidth: 0,
                }],
            },
            options: {
                cutout: '62%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { size: 11 } } } },
            },
        });
    </script>
@endsection
