@extends('layouts.app')

@section('title', 'Import orders')
@section('heading', '⬆️ Import orders from CSV')
@section('back', route('orders.index'))

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @if ($errors->any())
                <div class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                    @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('orders.import') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700">Which channel are these orders from?</label>
                    <select name="channel_id" required
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-500 focus:outline-none">
                        @foreach ($channels as $c)
                            <option value="{{ $c->id }}" @selected($c->slug === 'meesho')>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Commission &amp; shipping defaults for this channel are applied automatically.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">CSV file</label>
                    <input type="file" name="file" accept=".csv,text/csv" required
                           class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700 focus:border-brand-500 focus:outline-none">
                </div>

                <button class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Import orders</button>
            </form>
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-slate-900">📄 Expected format</h3>
            <p class="mt-2 text-sm text-slate-500">Your CSV needs these column headers (one row per item; same order # repeats for multi-item orders):</p>
            <ul class="mt-3 space-y-1 text-xs text-slate-600">
                @foreach (['order_number', 'order_date', 'sku', 'product_name', 'quantity', 'sale_price', 'customer_name', 'customer_phone', 'status'] as $col)
                    <li><code class="rounded bg-slate-100 px-1.5 py-0.5">{{ $col }}</code></li>
                @endforeach
            </ul>
            <a href="{{ route('orders.import.sample') }}" class="mt-4 inline-block rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">⬇️ Download sample CSV</a>
        </div>
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-700">
            <p class="font-semibold">How matching works</p>
            <ul class="mt-2 list-inside list-disc space-y-1 text-xs">
                <li>Products are matched by <strong>SKU</strong>, then by name.</li>
                <li>Unknown products are auto-created (cost ₹0 — update them later).</li>
                <li>Statuses like <em>delivered, shipped, RTO, returned</em> are auto-mapped.</li>
                <li>Stock is decremented for each imported sale.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
