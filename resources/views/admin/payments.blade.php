@extends('layouts.app')

@section('title', 'Admin · Payments')
@section('heading', 'All payments')

@section('content')
    <div class="mb-5 flex flex-wrap gap-2 text-sm">
        <a href="{{ route('admin.index') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.index') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Overview</a>
        <a href="{{ route('admin.users') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.users') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Users</a>
        <a href="{{ route('admin.payments') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.payments') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Payments</a>
        <a href="{{ route('admin.grants') }}" class="rounded-full px-3.5 py-1.5 font-medium {{ request()->routeIs('admin.grants') ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Grants</a>
    </div>

    <div class="mb-4 flex gap-2 text-sm">
        @php $statusLabels = ['' => 'All', 'captured' => 'Captured', 'failed' => 'Failed']; @endphp
        @foreach ($statusLabels as $value => $label)
            <a href="{{ route('admin.payments', array_filter(['status' => $value])) }}"
               class="rounded-full px-3 py-1 font-medium {{ ($status ?: '') === $value ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 border border-slate-200' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Plan</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Razorpay payment ID</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payments as $p)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-600">{{ $p->created_at->format('d M Y, h:i A') }}</td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ $p->user->name ?? 'Deleted user' }}</p>
                                <p class="text-xs text-slate-400">{{ $p->user->email ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ ucfirst($p->plan_type) }}</td>
                            <td class="px-5 py-3 text-right font-semibold {{ $p->status === 'captured' ? 'text-emerald-600' : 'text-slate-400' }}">{{ rupees($p->amount) }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $p->status === 'captured' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ ucfirst($p->status) }}</span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-400">{{ $p->razorpay_payment_id }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No payments recorded yet.</td></tr>
                    @endforelse
                </tbody>
                @if ($payments->isNotEmpty())
                    <tfoot class="border-t-2 border-slate-200 bg-slate-50 text-sm font-semibold">
                        <tr>
                            <td class="px-5 py-3 text-slate-600" colspan="3">Captured total (this page)</td>
                            <td class="px-5 py-3 text-right text-emerald-600">{{ rupees($payments->where('status', 'captured')->sum('amount')) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $payments->links() }}</div>
@endsection
