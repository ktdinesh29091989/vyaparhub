@extends('layouts.app')

@section('title', "Start free. Upgrade when you're ready — ₹299/month")
@section('heading', "Start free. Upgrade when you're ready — ₹299/month")
@section('back', route('dashboard'))

@section('content')
<div class="mx-auto max-w-4xl">

    {{-- Current status --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 text-center shadow-sm">
        @if ($user->isPro() && $user->plan_type === 'annual')
            <p class="text-sm font-semibold text-emerald-600">💎 Pro Annual plan active until {{ $user->plan_expires_at->format('d M Y') }}</p>
        @elseif ($user->isPro())
            <p class="text-sm font-semibold text-emerald-600">✨ Pro plan active until {{ $user->plan_expires_at->format('d M Y') }}</p>
        @else
            <p class="text-sm font-semibold text-slate-600">You are on the Free plan</p>
        @endif
    </div>

    @if (session('upgrade_reason'))
        <div class="mb-6 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
            ⚠️ {{ session('upgrade_reason') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach ($errors->all() as $e) <p>{{ $e }}</p> @endforeach
        </div>
    @endif

    @php
        $isMonthlyPro = $user->isPro() && $user->plan_type === 'monthly';
        $isAnnualPro = $user->isPro() && $user->plan_type === 'annual';
    @endphp

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        {{-- Free --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-slate-900">Free {{ ! $user->isPro() ? '(current)' : '' }}</h3>
            <p class="mt-1 text-2xl font-bold text-slate-900">₹0</p>
            <ul class="mt-5 space-y-2.5 text-sm text-slate-600">
                <li>✅ Up to 5 products</li>
                <li>✅ Up to 15 orders/month</li>
                <li>✅ Basic P&amp;L report</li>
                <li>✅ Stock Health &amp; Dashboard</li>
                <li class="text-slate-400">✗ PDF Export</li>
                <li class="text-slate-400">✗ Sales Channels report</li>
                <li class="text-slate-400">✗ Priority support</li>
            </ul>
        </div>

        {{-- Pro Monthly --}}
        <div class="rounded-2xl border-2 border-brand-500 bg-white p-6 shadow-md">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-slate-900">Pro {{ $isMonthlyPro ? '(current)' : '' }}</h3>
                <span class="rounded-full bg-brand-100 px-2.5 py-1 text-xs font-semibold text-brand-700">Best value</span>
            </div>
            <p class="mt-1 text-2xl font-bold text-slate-900">₹299<span class="text-sm font-medium text-slate-500">/month</span></p>
            <ul class="mt-5 space-y-2.5 text-sm text-slate-700">
                <li>✅ Unlimited products</li>
                <li>✅ Unlimited orders</li>
                <li>✅ PDF Export</li>
                <li>✅ Sales Channels report</li>
                <li>✅ Priority WhatsApp support</li>
                <li>✅ New features first</li>
            </ul>

            @if ($isMonthlyPro)
                <button type="button" onclick="startCheckout('monthly')" class="mt-6 w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    Renew Pro — ₹299
                </button>
            @else
                <button type="button" onclick="startCheckout('monthly')" class="mt-6 w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    Upgrade to Pro
                </button>
            @endif
            <p class="mt-2 text-center text-xs text-slate-400">Pay via UPI · GPay · PhonePe · Netbanking · Cards</p>
        </div>

        {{-- Pro Annual --}}
        <div class="rounded-2xl border-2 border-brand-500 bg-white p-6 shadow-md">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-slate-900">Pro Annual {{ $isAnnualPro ? '(current)' : '' }}</h3>
                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Save ₹1,089 — 3 months free</span>
            </div>
            <p class="mt-1 text-2xl font-bold text-slate-900">₹2,499<span class="text-sm font-medium text-slate-500">/year</span></p>
            <ul class="mt-5 space-y-2.5 text-sm text-slate-700">
                <li>✅ Unlimited products</li>
                <li>✅ Unlimited orders</li>
                <li>✅ PDF Export</li>
                <li>✅ Sales Channels report</li>
                <li>✅ Priority WhatsApp support</li>
                <li>✅ New features first</li>
            </ul>

            @if ($isAnnualPro)
                <button type="button" onclick="startCheckout('annual')" class="mt-6 w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    Renew Pro — ₹2,499
                </button>
            @else
                <button type="button" onclick="startCheckout('annual')" class="mt-6 w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    Upgrade to Pro Annual
                </button>
            @endif
            <p class="mt-2 text-center text-xs text-slate-400">Pay via UPI · GPay · PhonePe · Netbanking · Cards</p>
        </div>
    </div>

    <p class="mt-6 text-center text-xs text-slate-400">Payments are processed securely via Razorpay (test mode).</p>
</div>

<form id="verify-form" method="POST" action="{{ route('subscription.verify') }}" class="hidden">
    @csrf
    <input type="hidden" name="razorpay_payment_id">
    <input type="hidden" name="razorpay_order_id">
    <input type="hidden" name="razorpay_signature">
</form>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    async function startCheckout(planType) {
        const resp = await fetch("{{ route('subscription.create') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ plan_type: planType }),
        });
        const order = await resp.json();

        const rzp = new Razorpay({
            key: order.key,
            amount: order.amount,
            currency: order.currency,
            name: order.name,
            description: order.description,
            order_id: order.order_id,
            prefill: order.prefill,
            theme: { color: order.theme_color },
            handler: function (response) {
                const form = document.getElementById('verify-form');
                form.querySelector('[name="razorpay_payment_id"]').value = response.razorpay_payment_id;
                form.querySelector('[name="razorpay_order_id"]').value = response.razorpay_order_id;
                form.querySelector('[name="razorpay_signature"]').value = response.razorpay_signature;
                form.submit();
            },
        });
        rzp.open();
    }

    @if ($autoOpenPlan)
        document.addEventListener('DOMContentLoaded', () => startCheckout(@json($autoOpenPlan)));
    @endif
</script>
@endsection
