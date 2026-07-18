@extends('layouts.app')

@section('title', 'Admin · Users')
@section('heading', 'All users')

@section('content')
    <div class="mb-5 flex flex-wrap gap-2 text-sm">
        <a href="{{ route('admin.index') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.index') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Overview</a>
        <a href="{{ route('admin.users') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.users') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Users</a>
        <a href="{{ route('admin.payments') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.payments') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Payments</a>
        <a href="{{ route('admin.grants') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.grants') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Grants</a>
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
                        <th class="px-5 py-3">Actions</th>
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
                            <td class="px-5 py-3" x-data="{ open: false }">
                                <div class="flex items-center gap-3">
                                    <button type="button" @click="open = !open" class="text-xs font-medium text-brand-600 hover:text-brand-700">Grant/Extend Pro</button>
                                    <a href="{{ route('admin.grants', ['user_id' => $u->id]) }}" class="text-xs text-slate-400 hover:text-slate-600">History</a>
                                </div>
                                <form x-show="open" x-cloak method="POST" action="{{ route('admin.users.grant-pro', $u) }}" class="mt-2 w-56 space-y-1.5 rounded-lg border border-slate-200 bg-slate-50 p-3">
                                    @csrf
                                    <div class="flex gap-1.5">
                                        <input type="number" name="days" value="30" min="1" max="3650" required
                                               class="w-20 rounded-md border-slate-300 text-xs focus:border-brand-500 focus:ring-brand-500" placeholder="Days">
                                        <select name="plan_type" required class="rounded-md border-slate-300 text-xs focus:border-brand-500 focus:ring-brand-500">
                                            <option value="monthly">Monthly</option>
                                            <option value="annual">Annual</option>
                                        </select>
                                    </div>
                                    <input type="text" name="reason" required maxlength="255" placeholder="Reason (e.g. Bank transfer)"
                                           class="w-full rounded-md border-slate-300 text-xs focus:border-brand-500 focus:ring-brand-500">
                                    <button type="submit" class="w-full rounded-md bg-brand-600 px-2 py-1 text-xs font-semibold text-white hover:bg-brand-700">Confirm grant</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center text-slate-400">No users yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
