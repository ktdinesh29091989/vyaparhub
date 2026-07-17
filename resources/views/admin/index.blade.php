@extends('layouts.app')

@section('title', 'Admin')
@section('heading', 'Admin overview')

@section('content')
    <div class="mb-5 flex flex-wrap gap-2 text-sm">
        <a href="{{ route('admin.index') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.index') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Overview</a>
        <a href="{{ route('admin.users') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.users') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Users</a>
        <a href="{{ route('admin.payments') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.payments') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Payments</a>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @php
            $tiles = [
                ['👥', 'Total users', number_format($stats['total_users']), 'text-slate-900'],
                ['✨', 'Pro subscribers', number_format($stats['pro_users']), 'text-brand-600'],
                ['🆓', 'Free plan', number_format($stats['free_users']), 'text-slate-500'],
                ['🆕', 'New users this month', number_format($stats['new_users_this_month']), 'text-slate-900'],
                ['💰', 'Total revenue (all time)', rupees($stats['total_revenue']), 'text-emerald-600'],
                ['📅', 'Revenue this month', rupees($stats['revenue_this_month']), 'text-emerald-600'],
                ['⚠️', 'Failed payments this month', number_format($stats['failed_payments_this_month']), $stats['failed_payments_this_month'] ? 'text-red-600' : 'text-slate-500'],
            ];
        @endphp
        @foreach ($tiles as $t)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-xl">{{ $t[0] }}</span>
                <p class="mt-4 text-sm font-medium text-slate-500">{{ $t[1] }}</p>
                <p class="mt-1 text-2xl font-bold {{ $t[3] }}">{{ $t[2] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-slate-900">Recent payments</h3>
                <a href="{{ route('admin.payments') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View all</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($recentPayments as $p)
                    <div class="flex items-center justify-between border-t border-slate-100 pt-3 first:border-0 first:pt-0">
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $p->user->name ?? 'Deleted user' }}</p>
                            <p class="text-xs text-slate-400">{{ ucfirst($p->plan_type) }} · {{ $p->created_at->format('d M Y, h:i A') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-semibold {{ $p->status === 'captured' ? 'text-emerald-600' : 'text-red-500 line-through' }}">{{ rupees($p->amount) }}</span>
                            @if ($p->status !== 'captured')
                                <span class="block text-xs font-medium text-red-500">{{ ucfirst($p->status) }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No payments recorded yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-slate-900">Recent signups</h3>
                <a href="{{ route('admin.users') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View all</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($recentUsers as $u)
                    <div class="flex items-center justify-between border-t border-slate-100 pt-3 first:border-0 first:pt-0">
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $u->name }}</p>
                            <p class="text-xs text-slate-400">{{ $u->email }} · {{ $u->created_at->format('d M Y') }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $u->plan === 'pro' ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ ucfirst($u->plan) }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No users yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="font-semibold text-slate-900">⏳ Renewals due soon <span class="font-normal text-slate-400">(next 7 days)</span></h3>
        <div class="mt-4 space-y-3">
            @forelse ($renewalsDueSoon as $u)
                @php $daysLeft = (int) now()->startOfDay()->diffInDays($u->plan_expires_at->startOfDay(), false); @endphp
                <div class="flex items-center justify-between border-t border-slate-100 pt-3 first:border-0 first:pt-0">
                    <div>
                        <p class="text-sm font-medium text-slate-800">{{ $u->name }}</p>
                        <p class="text-xs text-slate-400">{{ $u->email }} · {{ ucfirst($u->plan_type ?? 'monthly') }} · expires {{ $u->plan_expires_at->format('d M Y') }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $daysLeft <= 2 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $daysLeft <= 0 ? 'Expires today' : $daysLeft.' day'.($daysLeft === 1 ? '' : 's').' left' }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-slate-400">No Pro renewals due in the next 7 days.</p>
            @endforelse
        </div>
    </div>
@endsection
