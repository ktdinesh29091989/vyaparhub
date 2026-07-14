@extends('layouts.auth')

@section('title', 'Welcome')

@section('content')
    <div class="text-center">
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-3xl">🧵</div>
        <h2 class="text-2xl font-bold text-slate-900">Welcome to VyaparHub!</h2>
        <p class="mt-3 text-sm text-slate-500">
            Add your first product to get started. Once it's in, you'll see it on your dashboard
            along with stock, orders and profit — all in one place.
        </p>

        <a href="{{ route('products.create') }}"
           class="mt-8 inline-block w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:ring-2 focus:ring-brand-500/40 focus:outline-none">
            + Add your first product
        </a>

        <a href="{{ route('dashboard') }}" class="mt-4 block text-sm font-medium text-slate-500 hover:text-slate-700">
            Skip for now →
        </a>
    </div>
@endsection
