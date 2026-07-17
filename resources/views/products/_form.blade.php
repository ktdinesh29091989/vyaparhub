@php
    $isEdit = isset($product);
    $val = fn ($key, $default = '') => old($key, $isEdit ? $product->{$key} : $default);
    $customAttributesJson = old('custom_attributes', $isEdit ? ($product->custom_attributes ?? []) : []);
@endphp

@if ($errors->any())
    <div class="mb-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
        Please fix the errors below.
    </div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2"
     x-data="{
        category: '{{ $val('category', 'textile') }}',
        fields: {{ Js::from($categoryFields) }},
        customAttributes: {{ Js::from($customAttributesJson) }}
     }">
    <div class="lg:col-span-2">
        <label class="block text-sm font-medium text-slate-700">Product name <span class="text-red-500">*</span></label>
        <input name="name" value="{{ $val('name') }}" required placeholder="Banarasi Silk Saree - Red"
               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none @error('name') border-red-400 @enderror">
        @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">SKU / code</label>
        <input name="sku" value="{{ $val('sku') }}" placeholder="SAR-RED-01"
               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none @error('sku') border-red-400 @enderror">
        @error('sku') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Category <span class="text-red-500">*</span></label>
        <select name="category" x-model="category"
                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none @error('category') border-red-400 @enderror">
            @foreach ($categories as $slug => $label)
                <option value="{{ $slug }}" @selected($val('category', 'textile') === $slug)>{{ $label }}</option>
            @endforeach
        </select>
        @error('category') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div x-show="category === 'textile'" x-cloak>
        <label class="block text-sm font-medium text-slate-700">Product type</label>
        <select name="product_type" :disabled="category !== 'textile'"
                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
            <option value="">— Select —</option>
            @foreach ($productTypes as $type)
                <option value="{{ $type }}" @selected($val('product_type') === $type)>{{ $type }}</option>
            @endforeach
        </select>
    </div>

    <div class="lg:col-span-2" x-show="(fields[category] || []).length > 0" x-cloak>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="mb-3 text-sm font-medium text-slate-700">Category details</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <template x-for="field in (fields[category] || [])" :key="field.key">
                    <div>
                        <label class="block text-sm font-medium text-slate-700" x-text="field.label"></label>
                        <template x-if="field.type === 'textarea'">
                            <textarea :name="`custom_attributes[${field.key}]`" x-model="customAttributes[field.key]" rows="2"
                                      class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none"></textarea>
                        </template>
                        <template x-if="field.type !== 'textarea'">
                            <input :type="field.type" :name="`custom_attributes[${field.key}]`" x-model="customAttributes[field.key]"
                                   class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Where to source</label>
        <input name="source_location" value="{{ $val('source_location') }}" placeholder="e.g. Elampillai weavers direct"
               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none @error('source_location') border-red-400 @enderror">
        @error('source_location') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Cost price (₹) <span class="text-red-500">*</span></label>
        <input name="cost_price" type="number" step="0.01" min="0" value="{{ $val('cost_price', 0) }}" required
               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none @error('cost_price') border-red-400 @enderror">
        @error('cost_price') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Selling price (₹) <span class="text-red-500">*</span></label>
        <input name="selling_price" type="number" step="0.01" min="0" value="{{ $val('selling_price', 0) }}" required
               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none @error('selling_price') border-red-400 @enderror">
        @error('selling_price') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">GST % <span class="text-red-500">*</span></label>
        <input name="gst_percent" type="number" step="0.01" min="0" max="100" value="{{ $val('gst_percent', 5) }}" required
               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none @error('gst_percent') border-red-400 @enderror">
        @error('gst_percent') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Low-stock alert at <span class="text-red-500">*</span></label>
        <input name="stock_threshold" type="number" min="0" value="{{ $val('stock_threshold', 5) }}" required
               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none @error('stock_threshold') border-red-400 @enderror">
        @error('stock_threshold') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    @unless ($isEdit)
        <div>
            <label class="block text-sm font-medium text-slate-700">Opening stock</label>
            <input name="stock" type="number" min="0" value="{{ old('stock', 0) }}"
                   class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
            <p class="mt-1 text-xs text-slate-400">How many units you currently hold.</p>
        </div>
    @endunless
</div>

<div class="mt-8 flex items-center gap-3">
    <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
        {{ $isEdit ? 'Save changes' : 'Add product' }}
    </button>
    <a href="{{ route('products.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</a>
</div>
