@extends('layouts.app')

@section('title', 'Admin · Users')
@section('heading', 'All users')

@section('content')
    <div class="mb-5 flex flex-wrap gap-2 text-sm">
        <a href="{{ route('admin.index') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.index') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Overview</a>
        <a href="{{ route('admin.users') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.users') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Users</a>
        <a href="{{ route('admin.payments') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.payments') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Payments</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Plan</th>
                        <th class="px-5 py-3">Expires</th>
                        <th class="px-5 py-3 text-center">Products</th>
                        <th class="px-5 py-3 text-center">Orders</th>
                        <th class="px-5 py-3">Joined</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $u)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ $u->name }} @if($u->is_admin) <span class="text-xs text-brand-600">(admin)</span> @endif</p>
                                <p class="text-xs text-slate-400">{{ $u->email }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $u->plan === 'pro' ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($u->plan) }}{{ $u->plan === 'pro' && $u->plan_type ? ' · '.ucfirst($u->plan_type) : '' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $u->plan_expires_at?->format('d M Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $u->products_count }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $u->orders_count }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $u->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No users yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
