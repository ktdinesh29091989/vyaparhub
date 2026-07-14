{{-- Reusable "+ Add Stock" button + modal. Expects $product in scope. --}}
<div x-data="{ addStock: {{ old('_add_stock_product') == $product->id ? 'true' : 'false' }} }">
    <button type="button" @click="addStock = true" class="font-medium text-emerald-600 hover:text-emerald-700">+ Add Stock</button>

    <template x-teleport="body">
        <div x-show="addStock" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
             @keydown.escape.window="addStock = false">
            <div @click.outside="addStock = false" class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-slate-900">Add stock</h3>
                <p class="text-sm text-slate-500">{{ $product->name }}</p>

                <form method="POST" action="{{ route('products.stock.add', $product) }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="hidden" name="_add_stock_product" value="{{ $product->id }}">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Quantity</label>
                        <input name="quantity" type="number" min="1" value="{{ old('_add_stock_product') == $product->id ? old('quantity') : '' }}" required
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none @error('quantity') border-red-400 @enderror">
                        @error('quantity') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Note (optional)</label>
                        <input name="note" type="text" placeholder="e.g. New batch from mill"
                               value="{{ old('_add_stock_product') == $product->id ? old('note') : '' }}"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Add stock</button>
                        <button type="button" @click="addStock = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
