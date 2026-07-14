@extends('layouts.app')

@section('title', 'New order')
@section('heading', 'New order')
@section('back', route('orders.index'))

@section('content')
@php
    $productsJson = $products->map(fn ($p) => [
        'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku,
        'price' => (float) $p->selling_price, 'cost' => (float) $p->cost_price, 'stock' => $p->stock,
    ])->values();
    $channelsJson = $channels->map(fn ($c) => [
        'id' => $c->id, 'name' => $c->name, 'slug' => $c->slug,
        'commission_percent' => (float) $c->commission_percent, 'shipping_charge' => (float) $c->shipping_charge,
    ])->values();
@endphp

@if ($errors->any())
    <div class="mb-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('orders.store') }}"
      x-data="orderForm({{ Js::from($productsJson) }}, {{ Js::from($channelsJson) }})"
      class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    @csrf

    {{-- Left: line items --}}
    <div class="space-y-6 lg:col-span-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-slate-900">Products</h3>
                <button type="button" @click="addLine()" class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-700">+ Add line</button>
            </div>

            <div class="mt-4 space-y-3">
                <template x-for="(line, i) in lines" :key="i">
                    <div class="grid grid-cols-12 items-start gap-2 rounded-lg border border-slate-100 bg-slate-50 p-3">
                        <div class="col-span-12 sm:col-span-6">
                            <select :name="`items[${i}][product_id]`" x-model.number="line.product_id" @change="onProduct(i)"
                                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
                                <option value="">— Select product —</option>
                                <template x-for="p in products" :key="p.id">
                                    <option :value="p.id" x-text="`${p.name} (stock ${p.stock})`"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-span-4 sm:col-span-2">
                            <input type="number" min="1" :name="`items[${i}][quantity]`" x-model.number="line.quantity"
                                   class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none" placeholder="Qty">
                            <p class="mt-1 text-xs" x-show="line.product_id"
                               :class="(line.quantity || 0) > stockFor(line.product_id) ? 'font-medium text-red-500' : 'text-slate-400'"
                               x-text="'Available stock: ' + stockFor(line.product_id)"></p>
                        </div>
                        <div class="col-span-5 sm:col-span-3">
                            <input type="number" step="0.01" min="0" :name="`items[${i}][sale_price]`" x-model.number="line.sale_price"
                                   class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none" placeholder="₹ price">
                        </div>
                        <div class="col-span-3 flex justify-end sm:col-span-1">
                            <button type="button" @click="removeLine(i)" x-show="lines.length > 1"
                                    class="rounded-lg p-2 text-red-500 hover:bg-red-50">✕</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-semibold text-slate-900">Customer (optional)</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <input name="customer_name" value="{{ old('customer_name') }}" placeholder="Customer name"
                       class="rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-500 focus:outline-none">
                <input name="customer_phone" value="{{ old('customer_phone') }}" placeholder="Phone"
                       class="rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-500 focus:outline-none">
            </div>
            <textarea name="notes" rows="2" placeholder="Notes (optional)"
                      class="mt-4 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-500 focus:outline-none">{{ old('notes') }}</textarea>
        </div>
    </div>

    {{-- Right: order meta + totals --}}
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-semibold text-slate-900">Order details</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Channel</label>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="c in channels" :key="c.id">
                            <button type="button" @click="channelId = c.id; applyChannel()"
                                    :class="channelId === c.id ? channelActiveClass(c.slug) : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'"
                                    class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                                    x-text="c.name"></button>
                        </template>
                    </div>
                    <input type="hidden" name="channel_id" :value="channelId">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Order date</label>
                    <input type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}"
                           class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Status</label>
                    <select name="status" class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm capitalize focus:border-brand-500 focus:outline-none">
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}" @selected(old('status','placed') === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="selectedChannelSlug && selectedChannelSlug !== 'local' && selectedChannelSlug !== 'whatsapp'">
                    <label class="block text-sm font-medium text-slate-700"
                           x-text="(selectedChannelName || 'Channel') + ' Order ID (optional)'"></label>
                    <input name="order_number" value="{{ old('order_number') }}"
                           placeholder="Order ID from this channel — leave blank to auto-generate"
                           class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-semibold text-slate-900">Totals</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="font-medium">₹<span x-text="subtotal.toFixed(0)"></span></span></div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Commission (₹)</span>
                    <input type="number" step="0.01" min="0" name="commission_amount" x-model.number="commission"
                           class="w-24 rounded-lg border border-slate-300 px-2 py-1 text-right text-sm focus:border-brand-500 focus:outline-none">
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Shipping (₹)</span>
                    <input type="number" step="0.01" min="0" name="shipping_charge" x-model.number="shipping"
                           class="w-24 rounded-lg border border-slate-300 px-2 py-1 text-right text-sm focus:border-brand-500 focus:outline-none">
                </div>
                <div class="flex justify-between border-t border-slate-100 pt-3">
                    <span class="text-slate-500">Est. cost of goods</span><span class="font-medium">₹<span x-text="cogs.toFixed(0)"></span></span>
                </div>
                <div class="flex justify-between border-t border-slate-100 pt-3 text-base">
                    <span class="font-semibold text-slate-800">Est. net profit</span>
                    <span class="font-bold" :class="netProfit >= 0 ? 'text-emerald-600' : 'text-red-600'">₹<span x-text="netProfit.toFixed(0)"></span></span>
                </div>
            </div>
            <button type="submit" class="mt-5 w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Create order</button>
        </div>
    </div>
</form>

<script>
function orderForm(products, channels) {
    return {
        products,
        channels,
        lines: [{ product_id: '', quantity: 1, sale_price: null, cost: 0 }],
        channelId: {{ old('channel_id', $channels->first()->id ?? 'null') }},
        commission: 0,
        shipping: 0,
        _meta: {},
        init() {
            this.applyChannel();
        },
        addLine() { this.lines.push({ product_id: '', quantity: 1, sale_price: null, cost: 0 }); },
        removeLine(i) { this.lines.splice(i, 1); },
        onProduct(i) {
            const p = this.products.find(x => x.id === this.lines[i].product_id);
            if (p) { this.lines[i].sale_price = p.price; }
            this.recalc();
        },
        stockFor(productId) {
            const p = this.products.find(x => x.id === productId);
            return p ? p.stock : null;
        },
        get selectedChannelSlug() {
            const c = this.channels.find(x => x.id === this.channelId);
            return c ? c.slug : null;
        },
        get selectedChannelName() {
            const c = this.channels.find(x => x.id === this.channelId);
            return c ? c.name : null;
        },
        channelActiveClass(slug) {
            return {
                meesho: 'border-pink-600 bg-pink-600 text-white',
                amazon: 'border-orange-600 bg-orange-600 text-white',
                whatsapp: 'border-emerald-600 bg-emerald-600 text-white',
                local: 'border-blue-600 bg-blue-600 text-white',
            }[slug] || 'border-slate-800 bg-slate-800 text-white';
        },
        applyChannel() {
            const c = this.channels.find(x => x.id === this.channelId);
            if (c) {
                this.shipping = parseFloat(c.shipping_charge || 0);
                this._meta.commissionPct = parseFloat(c.commission_percent || 0);
            }
            this.recalc();
        },
        recalc() {
            this.commission = Math.round(this.subtotal * (this._meta.commissionPct || 0) / 100 * 100) / 100;
        },
        get subtotal() {
            return this.lines.reduce((s, l) => s + (l.quantity || 0) * (l.sale_price || 0), 0);
        },
        get cogs() {
            return this.lines.reduce((s, l) => {
                const p = this.products.find(x => x.id === l.product_id);
                return s + (l.quantity || 0) * (p ? (p.cost || 0) : 0);
            }, 0);
        },
        get netProfit() {
            return this.subtotal - this.cogs - (this.commission || 0) - (this.shipping || 0);
        },
    };
}
</script>
@endsection
