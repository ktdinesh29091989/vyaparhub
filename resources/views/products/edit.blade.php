@extends('layouts.app')

@section('title', 'Edit product')
@section('heading', 'Edit product')
@section('back', route('products.index'))

@section('content')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Edit form --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <form method="POST" action="{{ route('products.update', $product) }}">
                    @csrf
                    @method('PUT')
                    @include('products._form')
                </form>
            </div>
        </div>

        {{-- Stock + history sidebar --}}
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Current stock</h3>
                <p class="mt-2 text-3xl font-bold {{ $product->is_low_stock ? 'text-amber-600' : 'text-slate-900' }}">{{ $product->stock }}</p>
                <p class="text-xs text-slate-400">Alert below {{ $product->stock_threshold }}</p>

                <form method="POST" action="{{ route('products.stock', $product) }}" class="mt-5 space-y-3 border-t border-slate-100 pt-5">
                    @csrf
                    <p class="text-sm font-medium text-slate-700">Correction (+/-)</p>
                    <p class="text-xs text-slate-400">For stock counts / corrections. To record new stock coming in, use "+ Add Stock" on the products list.</p>
                    <input name="quantity" type="number" placeholder="e.g. 10 or -2" required
                           class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none @error('quantity') border-red-400 @enderror">
                    @error('quantity') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <input name="note" placeholder="Note (optional)" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                    <button class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Update stock</button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Recent movements</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($movements as $m)
                        <div class="flex items-center justify-between text-sm">
                            <div>
                                <p class="font-medium capitalize text-slate-700">{{ $m->type }}</p>
                                <p class="text-xs text-slate-400">{{ $m->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                            <span class="font-semibold {{ $m->quantity >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $m->quantity >= 0 ? '+' : '' }}{{ $m->quantity }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">No movements yet.</p>
                    @endforelse
                </div>
            </div>

            <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button class="w-full rounded-lg border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50">Delete product</button>
            </form>
        </div>
    </div>
@endsection
