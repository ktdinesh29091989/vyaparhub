@extends('layouts.app')

@section('title', 'WhatsApp quick entry')
@section('heading', '💬 Quick order')
@section('back', route('orders.index'))

@section('content')
@php
    $productsJson = $products->map(fn ($p) => [
        'id' => $p->id, 'name' => $p->name, 'price' => (float) $p->selling_price,
        'cost' => (float) $p->cost_price, 'stock' => $p->stock,
    ])->values();
    $waChannel = $channels->firstWhere('slug', 'whatsapp') ?? $channels->first();
@endphp

@if ($errors->any())
    <div class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
        </ul>
    </div>
@endif

<div class="mx-auto max-w-md">
    <p class="mb-4 text-sm text-slate-500">Taking an order on WhatsApp? Punch it in here while you chat — stock updates instantly.</p>

    <form method="POST" action="{{ route('orders.store') }}" x-data="quickOrder({{ Js::from($productsJson) }})" class="space-y-5">
        @csrf
        <input type="hidden" name="source" value="whatsapp">
        <input type="hidden" name="channel_id" value="{{ $waChannel->id ?? '' }}">
        <input type="hidden" name="order_date" value="{{ now()->toDateString() }}">
        <input type="hidden" name="status" value="placed">
        <input type="hidden" name="commission_amount" value="0">
        <input type="hidden" name="shipping_charge" :value="shipping">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-3 font-semibold text-slate-900">Items</h3>
            <div class="space-y-3">
                <template x-for="(line, i) in lines" :key="i">
                    <div class="grid grid-cols-12 items-center gap-2">
                        <div class="col-span-8">
                            <select :name="`items[${i}][product_id]`" x-model.number="line.product_id" @change="onProduct(i)"
                                    class="block w-full min-w-0 truncate rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none">
                                <option value="">— Product —</option>
                                <template x-for="p in products" :key="p.id">
                                    <option :value="p.id" x-text="`${p.name} (₹${p.price}, ${p.stock} left)`"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-span-3">
                            <input type="number" min="1" :name="`items[${i}][quantity]`" x-model.number="line.quantity"
                                   class="block w-full rounded-lg border border-slate-300 px-2 py-2.5 text-center text-sm focus:border-brand-500 focus:outline-none">
                        </div>
                        <input type="hidden" :name="`items[${i}][sale_price]`" :value="line.sale_price">
                        <div class="col-span-1 flex justify-end">
                            <button type="button" @click="removeLine(i)" x-show="lines.length > 1" class="p-2 text-red-500">✕</button>
                        </div>
                    </div>
                </template>
            </div>
            <button type="button" @click="addLine()" class="mt-3 text-sm font-semibold text-brand-600 hover:text-brand-700">+ Add another item</button>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-3 font-semibold text-slate-900">Customer</h3>
            <input name="customer_name" value="{{ old('customer_name') }}" placeholder="Name"
                   class="mb-3 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-500 focus:outline-none">
            <input name="customer_phone" value="{{ old('customer_phone') }}" placeholder="WhatsApp number" inputmode="tel"
                   class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-500 focus:outline-none">
            <div class="mt-3 flex items-center justify-between">
                <label class="text-sm text-slate-600">Shipping (₹)</label>
                <input type="number" step="0.01" min="0" x-model.number="shipping"
                       class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-right text-sm focus:border-brand-500 focus:outline-none">
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
            <div class="flex items-center justify-between text-sm">
                <span class="text-emerald-700">Order total</span>
                <span class="text-lg font-bold text-emerald-800">₹<span x-text="subtotal.toFixed(0)"></span></span>
            </div>
            <div class="mt-1 flex items-center justify-between text-xs text-emerald-600">
                <span>Est. profit after cost & shipping</span>
                <span class="font-semibold">₹<span x-text="netProfit.toFixed(0)"></span></span>
            </div>
        </div>

        <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3.5 text-base font-bold text-white shadow-sm hover:bg-emerald-700">
            ✅ Save order
        </button>
    </form>
</div>

<script>
function quickOrder(products) {
    return {
        products,
        lines: [{ product_id: '', quantity: 1, sale_price: 0 }],
        shipping: {{ (float) ($waChannel->shipping_charge ?? 0) }},
        addLine() { this.lines.push({ product_id: '', quantity: 1, sale_price: 0 }); },
        removeLine(i) { this.lines.splice(i, 1); },
        onProduct(i) {
            const p = this.products.find(x => x.id === this.lines[i].product_id);
            this.lines[i].sale_price = p ? p.price : 0;
        },
        get subtotal() { return this.lines.reduce((s, l) => s + (l.quantity||0) * (l.sale_price||0), 0); },
        get cogs() {
            return this.lines.reduce((s, l) => {
                const p = this.products.find(x => x.id === l.product_id);
                return s + (l.quantity||0) * (p ? p.cost : 0);
            }, 0);
        },
        get netProfit() { return this.subtotal - this.cogs - (this.shipping||0); },
    };
}
</script>
@endsection
