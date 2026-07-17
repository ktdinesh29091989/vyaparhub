<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VyaparHub — Track orders across every channel. Know your real profit.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.analytics')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#fdf2f8', 100: '#fce7f3', 200: '#fbcfe8',
                            400: '#f472b6', 500: '#ec4899', 600: '#db2777',
                            700: '#be185d', 800: '#9d174d', 900: '#831843',
                        },
                    },
                },
            },
        }
    </script>
</head>
<body class="h-full font-sans bg-white text-slate-800 antialiased">

    {{-- Header --}}
    <header class="sticky top-0 z-30 border-b border-slate-100 bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
            <a href="{{ route('landing') }}" class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-lg text-white">🧵</div>
                <span class="text-lg font-extrabold tracking-tight text-slate-900">VyaparHub</span>
            </a>
            <nav class="hidden items-center gap-8 sm:flex">
                <a href="#features" class="text-sm font-medium text-slate-600 hover:text-slate-900">Features</a>
                <a href="#pricing" class="text-sm font-medium text-slate-600 hover:text-slate-900">Pricing</a>
                <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Login</a>
            </nav>
            <a href="{{ route('register') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                Start Free
            </a>
        </div>
    </header>

    {{-- Hero --}}
    <section class="mx-auto max-w-4xl px-4 pb-16 pt-16 text-center sm:px-6 sm:pt-24">
        <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-5xl">
            Track every order.<br class="hidden sm:block"> Know your real profit.
        </h1>
        <p class="mx-auto mt-5 max-w-2xl text-base text-slate-600 sm:text-lg">
            VyaparHub is the simplest tool for Indian textile sellers to manage stock, track orders across Meesho, Amazon, WhatsApp and local sales — and see their exact profit in one place.
        </p>
        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="{{ route('register') }}" class="w-full rounded-lg bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 sm:w-auto">
                Start Free — No credit card needed
            </a>
            <a href="#features" class="w-full rounded-lg border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:w-auto">
                See how it works
            </a>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="border-t border-slate-100 bg-slate-50 py-16 sm:py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-6">
                    <div class="text-3xl">📦</div>
                    <h3 class="mt-4 text-lg font-bold text-slate-900">Smart Stock Management</h3>
                    <p class="mt-2 text-sm text-slate-600">Know exactly how many sarees, kurtis and dhotis you have. Get alerts before you run out. Log every restock in seconds.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6">
                    <div class="text-3xl">📊</div>
                    <h3 class="mt-4 text-lg font-bold text-slate-900">Real Profit per Order</h3>
                    <p class="mt-2 text-sm text-slate-600">See your exact profit after marketplace commission and shipping, on every channel. Know which products actually make you money.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6">
                    <div class="text-3xl">📱</div>
                    <h3 class="mt-4 text-lg font-bold text-slate-900">All Channels in One Place</h3>
                    <p class="mt-2 text-sm text-slate-600">Track Meesho, Amazon, WhatsApp and local shop orders together — and any other channel you sell on. One dashboard, complete picture.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Social proof --}}
    <section class="py-16 sm:py-20">
        <div class="mx-auto max-w-4xl px-4 text-center sm:px-6">
            <h2 class="text-2xl font-extrabold text-slate-900 sm:text-3xl">Built for India's textile sellers</h2>
            <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div class="rounded-2xl bg-brand-50 p-6">
                    <p class="text-2xl font-extrabold text-brand-700">Any Category</p>
                    <p class="mt-1 text-sm font-medium text-slate-600">Sarees, Kurtis, Lehengas &amp; more</p>
                </div>
                <div class="rounded-2xl bg-brand-50 p-6">
                    <p class="text-2xl font-extrabold text-brand-700">Meesho, Amazon, WhatsApp &amp; More</p>
                    <p class="mt-1 text-sm font-medium text-slate-600">All your channels, tracked</p>
                </div>
                <div class="rounded-2xl bg-brand-50 p-6">
                    <p class="text-2xl font-extrabold text-brand-700">₹0</p>
                    <p class="mt-1 text-sm font-medium text-slate-600">to start</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="border-t border-slate-100 bg-slate-50 py-16 sm:py-20">
        <div class="mx-auto max-w-4xl px-4 sm:px-6">
            <h2 class="text-center text-2xl font-extrabold text-slate-900 sm:text-3xl">Simple, honest pricing</h2>
            <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-3">
                {{-- Free --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900">Free</h3>
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
                    <a href="{{ route('register') }}" class="mt-6 block w-full rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Start Free
                    </a>
                </div>

                {{-- Pro Monthly --}}
                <div class="rounded-2xl border-2 border-brand-500 bg-white p-6 shadow-md">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-900">Pro</h3>
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
                    <a href="{{ route('register', ['intent' => 'pro']) }}" class="mt-6 block w-full rounded-lg bg-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-700">
                        Start Pro — ₹299/month
                    </a>
                    <p class="mt-2 text-center text-xs text-slate-400">Pay via UPI · GPay · PhonePe · Netbanking · Cards</p>
                </div>

                {{-- Pro Annual --}}
                <div class="rounded-2xl border-2 border-brand-500 bg-white p-6 shadow-md">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-900">Pro Annual</h3>
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
                    <a href="{{ route('register', ['intent' => 'annual']) }}" class="mt-6 block w-full rounded-lg bg-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-700">
                        Get Annual — ₹2,499/year
                    </a>
                    <p class="mt-2 text-center text-xs text-slate-400">Pay via UPI · GPay · PhonePe · Netbanking · Cards</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-slate-100 py-10">
        <div class="mx-auto flex max-w-6xl flex-col items-center gap-4 px-4 text-center sm:flex-row sm:justify-between sm:px-6 sm:text-left">
            <p class="text-sm text-slate-500">VyaparHub © {{ date('Y') }}</p>
            <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-slate-500">
                <a href="{{ route('login') }}" class="hover:text-slate-800">Login</a>
                <a href="{{ route('register') }}" class="hover:text-slate-800">Register</a>
                <a href="{{ route('privacy') }}" class="hover:text-slate-800">Privacy Policy</a>
                <a href="mailto:support@vyaparhub.in" class="hover:text-slate-800">Contact</a>
            </div>
            <p class="text-sm text-slate-500">Made in India 🇮🇳</p>
        </div>
    </footer>

</body>
</html>
