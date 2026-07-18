@extends('layouts.app')

@section('title', 'Admin · Grants')
@section('heading', 'Manual plan grants')

@section('content')
    <div class="mb-5 flex flex-wrap gap-2 text-sm">
        <a href="{{ route('admin.index') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.index') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Overview</a>
        <a href="{{ route('admin.users') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.users') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Users</a>
        <a href="{{ route('admin.payments') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.payments') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Payments</a>
        <a href="{{ route('admin.grants') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.grants') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Grants</a>
    </div>

    @if ($filterUser)
        <div class="mb-4 flex items-center gap-2 text-sm text-slate-600">
            <span>Showing grants for <strong>{{ $filterUser->name }}</strong></span>
            <a href="{{ route('admin.grants') }}" class="font-medium text-brand-600 hover:underline">Clear filter</a>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Granted by</th>
                        <th class="px-5 py-3 text-center">Days</th>
                        <th class="px-5 py-3">New expiry</th>
                        <th class="px-5 py-3">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($grants as $g)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-600">{{ $g->created_at->format('d M Y, h:i A') }}</td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ $g->user->name ?? 'Deleted user' }}</p>
                                <p class="text-xs text-slate-400">{{ $g->user->email ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $g->admin->name ?? 'Deleted admin' }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">+{{ $g->days_granted }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $g->new_expires_at->format('d M Y') }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $g->reason }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No manual grants recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $grants->links() }}</div>
@endsection
