@extends('layouts.auth')

@section('title', 'Verify your email')

@section('content')
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-2xl">📧</div>
        <h2 class="text-2xl font-bold text-slate-900">Check your email</h2>
        <p class="mt-2 text-sm text-slate-500">
            We sent a verification link to <strong>{{ auth()->user()->email }}</strong>.
            Click it to activate your account.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-center text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:ring-2 focus:ring-brand-500/40 focus:outline-none">
            Resend verification email
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="w-full text-center text-sm font-medium text-slate-500 hover:text-slate-700">
            Log out
        </button>
    </form>
@endsection
