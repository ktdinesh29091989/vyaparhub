@extends('layouts.app')

@section('title', 'Add product')
@section('heading', 'Add product')
@section('back', route('products.index'))

@section('content')
    <div class="mb-6 max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="text-sm font-semibold text-slate-800">Bulk add via CSV</h2>
        <p class="mt-1 text-sm text-slate-500">Upload a CSV to add many products at once, instead of filling the form below one at a time.</p>

        @if ($errors->has('file'))
            <p class="mt-3 text-sm text-red-600">{{ $errors->first('file') }}</p>
        @endif

        <form method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
            @csrf
            <input type="file" name="file" accept=".csv,text/csv" required
                   class="block w-full flex-1 rounded-lg border border-slate-300 text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
            <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Upload &amp; import</button>
        </form>

        <div class="mt-3 flex gap-4 text-sm">
            <a href="{{ route('products.import.template') }}" class="font-medium text-brand-600 hover:text-brand-700">Download blank template</a>
            <a href="{{ route('products.import.sample') }}" class="font-medium text-brand-600 hover:text-brand-700">Download sample CSV (Salem sourcing sheet)</a>
        </div>
    </div>

    <div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form method="POST" action="{{ route('products.store') }}">
            @csrf
            @include('products._form')
        </form>
    </div>
@endsection
