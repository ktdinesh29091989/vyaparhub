@extends('layouts.app')

@section('title', 'Profile')
@section('heading', 'Profile')
@section('back', route('dashboard'))

@section('content')
<div class="mx-auto max-w-2xl space-y-6">

    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    {{-- Plan status --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->isPro() ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-600' }}">
                    @if ($user->isPro() && $user->plan_type === 'annual')
                        💎 Pro Annual
                    @elseif ($user->isPro())
                        ✨ Pro
                    @else
                        Free
                    @endif
                </span>
                @if ($user->isPro())
                    <p class="mt-2 text-sm text-slate-500">Valid until {{ $user->plan_expires_at->format('d M Y') }}</p>
                @endif
            </div>
            @php
                $expiringSoon = $user->isPro() && now()->diffInDays($user->plan_expires_at) <= 7;
                $renewPrice = $user->plan_type === 'annual' ? \App\Models\User::ANNUAL_PRICE_RUPEES : \App\Models\User::PRO_PRICE_RUPEES;
            @endphp
            @if (! $user->isPro())
                <a href="{{ route('upgrade') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Upgrade to Pro</a>
            @elseif ($expiringSoon)
                <a href="{{ route('upgrade') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Renew Pro — ₹{{ number_format($renewPrice) }}</a>
            @endif
        </div>
    </div>

    {{-- Profile details --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 font-semibold text-slate-900">Business details</h3>
        @if ($errors->any() && $errors->missing('current_password'))
            <div class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
                @foreach ($errors->all() as $e) <p>{{ $e }}</p> @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-slate-700">Business name</label>
                <input name="business_name" value="{{ old('business_name', $user->business_name) }}"
                       class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input value="{{ $user->email }}" disabled
                       class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-500">
                <p class="mt-1 text-xs text-slate-400">Email can't be changed after verification.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Mobile number</label>
                <input name="mobile" value="{{ old('mobile', $user->mobile) }}" maxlength="10" inputmode="numeric"
                       class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
            </div>
            <button class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Save changes</button>
        </form>
    </div>

    {{-- Change password --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 font-semibold text-slate-900">Change password</h3>
        @if ($errors->has('current_password'))
            <div class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
                @foreach ($errors->all() as $e) <p>{{ $e }}</p> @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-slate-700">Current password</label>
                <input name="current_password" type="password" required autocomplete="current-password"
                       class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">New password</label>
                <input name="password" type="password" required autocomplete="new-password"
                       class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Confirm new password</label>
                <input name="password_confirmation" type="password" required autocomplete="new-password"
                       class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
            </div>
            <button class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Change password</button>
        </form>
    </div>
</div>
@endsection
