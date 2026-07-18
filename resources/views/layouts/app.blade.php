<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · VyaparHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter','sans-serif'] },
            colors: { brand: { 50:'#fdf2f8',100:'#fce7f3',200:'#fbcfe8',500:'#ec4899',600:'#db2777',700:'#be185d',800:'#9d174d' } } } } }
    </script>
</head>
<body class="h-full font-sans bg-slate-50 text-slate-800 antialiased" x-data="{ sidebar: false }">
<div class="min-h-full lg:flex">

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full transform bg-slate-900 text-slate-300 transition-transform lg:static lg:translate-x-0 print:hidden"
           :class="sidebar && 'translate-x-0'">
        <div class="flex h-16 items-center gap-2.5 px-6">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white p-1.5">
                <img src="{{ asset('images/vyaparhub-logo.png') }}" alt="VyaparHub" class="h-full w-full object-contain">
            </div>
            <span class="text-lg font-extrabold tracking-tight text-white">VyaparHub</span>
        </div>

        @php
            $nav = [
                ['dashboard', 'Dashboard', '📊'],
                ['products.index', 'Products & Stock', '📦'],
                ['orders.index', 'Orders', '🧾'],
                ['reports.profit', 'Returns & Profit', '↩️'],
                ['reports.pnl', 'P&L Report', '💰'],
            ];
            if (auth()->user()->isPro()) {
                $nav[] = ['reports.inventory', 'Stock Health', '📈'];
                $nav[] = ['channels.index', 'Sales Channels', '🛍️'];
            }
            if (auth()->user()->is_admin) {
                $nav[] = ['admin.index', 'Admin', '🛠️'];
            }
        @endphp

        <nav class="mt-4 space-y-1 px-3">
            @foreach ($nav as [$route, $label, $icon])
                @php $active = request()->routeIs(str_replace('.index', '', $route).'*'); @endphp
                <a href="{{ route($route) }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-brand-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <span class="text-base">{{ $icon }}</span> {{ $label }}
                </a>
            @endforeach

        </nav>
    </aside>

    {{-- Backdrop on mobile --}}
    <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    {{-- Main --}}
    <div class="flex min-h-screen flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6 print:hidden">
            <div class="flex items-center gap-3">
                <button @click="sidebar = true" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden">☰</button>
                @hasSection('back')
                    <a href="@yield('back')" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700" title="Back">←</a>
                @endif
                <h1 class="text-lg font-bold text-slate-900">@yield('heading', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-3">
                @if (auth()->user()->isExpiringSoon())
                    <a href="{{ route('upgrade') }}" class="hidden animate-blink items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 sm:flex"
                       title="Your plan expires on {{ auth()->user()->plan_expires_at->format('d M Y') }}">
                        ⚠️ Renew soon
                    </a>
                @endif
                @if (auth()->user()->plan === 'pro' && auth()->user()->plan_type === 'annual')
                    <span class="hidden rounded-full bg-brand-100 px-2.5 py-1 text-xs font-semibold text-brand-700 sm:block">💎 Pro Annual</span>
                @elseif (auth()->user()->plan === 'pro')
                    <span class="hidden rounded-full bg-brand-100 px-2.5 py-1 text-xs font-semibold text-brand-700 sm:block">✨ Pro</span>
                @endif
                <a href="{{ route('profile') }}" class="hidden text-sm text-slate-500 hover:text-slate-700 sm:block">{{ auth()->user()->business_name ?? auth()->user()->name }}</a>
                <a href="{{ route('profile') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Log out</button>
                </form>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            @if (session('status'))
                <div x-data="{ show: true }" x-show="show" class="mb-6 flex items-center justify-between rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    <span>✅ {{ session('status') }}</span>
                    <button @click="show = false" class="text-emerald-500 hover:text-emerald-700">✕</button>
                </div>
            @endif

            @if (session('import_skipped') && count(session('import_skipped')))
                <div x-data="{ show: true }" x-show="show" class="mb-6 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">⚠️ Some rows were skipped:</span>
                        <button @click="show = false" class="text-amber-500 hover:text-amber-700">✕</button>
                    </div>
                    <ul class="mt-2 list-inside list-disc space-y-0.5">
                        @foreach (session('import_skipped') as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
<style>
    [x-cloak]{display:none!important}
    @keyframes blink { 50% { opacity: 0.35; } }
    .animate-blink { animation: blink 1.1s ease-in-out infinite; }
</style>
</body>
</html>
