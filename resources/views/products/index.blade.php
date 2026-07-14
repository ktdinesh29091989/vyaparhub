@extends('layouts.app')

@section('title', 'Products & Stock')
@section('heading', 'Products & Stock')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-1 items-center gap-2 sm:max-w-md">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or SKU…"
                   class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
            @if (request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif
            <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Search</button>
        </form>
        <a href="{{ route('products.create') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-brand-700">+ Add product</a>
    </div>

    <div class="mb-4 flex gap-2 text-sm">
        <a href="{{ route('products.index') }}" class="rounded-full px-3 py-1 font-medium {{ !request('filter') ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 border border-slate-200' }}">All</a>
        <a href="{{ route('products.index', ['filter' => 'low']) }}" class="rounded-full px-3 py-1 font-medium {{ request('filter') === 'low' ? 'bg-amber-500 text-white' : 'bg-white text-slate-600 border border-slate-200' }}">⚠️ Low stock</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3 text-right">Cost</th>
                        <th class="px-5 py-3 text-right">Sell</th>
                        <th class="px-5 py-3 text-right">Margin</th>
                        <th class="px-5 py-3 text-center">Stock</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ $product->name }}</p>
                                <p class="text-xs text-slate-400">{{ $product->sku ?? 'no SKU' }}</p>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $product->category ?? '—' }}</td>
                            <td class="px-5 py-3 text-right text-slate-600">{{ rupees($product->cost_price) }}</td>
                            <td class="px-5 py-3 text-right text-slate-600">{{ rupees($product->selling_price) }}</td>
                            <td class="px-5 py-3 text-right font-medium {{ $product->unit_margin >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ rupees($product->unit_margin) }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold {{ $product->is_low_stock ? 'bg-yellow-100 text-yellow-800' : 'bg-emerald-100 text-emerald-700' }}">
                                    @if ($product->is_low_stock) <span title="Low stock">⚠️</span> @endif
                                    {{ $product->stock }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @include('products._add_stock_button', ['product' => $product])
                                    <a href="{{ route('products.edit', $product) }}" class="font-medium text-brand-600 hover:text-brand-700">Edit</a>
                                    <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="font-medium text-red-600 hover:text-red-700">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <p class="text-slate-400">No products yet.</p>
                                <a href="{{ route('products.create') }}" class="mt-3 inline-block rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">+ Add your first product</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
