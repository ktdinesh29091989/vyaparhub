<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') · VyaparHub</title>
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
<body class="h-full font-sans bg-slate-50 text-slate-800 antialiased">
    <div class="min-h-full lg:grid lg:grid-cols-2">

        {{-- Brand / value panel --}}
        <div class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-fuchsia-600 p-12 text-white">
            <div class="absolute -top-24 -right-24 h-80 w-80 rounded-full bg-white/10 blur-2xl"></div>
            <div class="absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-fuchsia-400/20 blur-3xl"></div>

            <div class="relative flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white p-2">
                    <img src="{{ asset('images/vyaparhub-logo.png') }}" alt="VyaparHub" class="h-full w-full object-contain">
                </div>
                <span class="text-xl font-extrabold tracking-tight">VyaparHub</span>
            </div>

            <div class="relative max-w-md">
                <h1 class="text-4xl font-extrabold leading-tight">
                    Run your business like a pro.
                </h1>
                <p class="mt-4 text-lg text-brand-100">
                    Inventory, orders &amp; profit — one clean dashboard built for Meesho,
                    Flipkart &amp; WhatsApp sellers across India, whatever you sell.
                </p>

                <ul class="mt-8 space-y-4">
                    @foreach ([
                        ['📦', 'Live inventory', 'Track stock across every catalog & size variant.'],
                        ['🛒', 'Order tracking', 'Sync orders, returns & RTOs in one place.'],
                        ['📈', 'Profit tracker', 'See real margins after shipping, GST & commission.'],
                    ] as $feature)
                        <li class="flex items-start gap-3">
                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-white/15 text-lg">{{ $feature[0] }}</span>
                            <div>
                                <p class="font-semibold">{{ $feature[1] }}</p>
                                <p class="text-sm text-brand-100">{{ $feature[2] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="relative flex items-center gap-4 text-sm text-brand-100">
                <span>🔒 Bank-grade security</span>
                <span class="h-1 w-1 rounded-full bg-brand-200"></span>
                <span>🇮🇳 Made for Indian sellers</span>
            </div>
        </div>

        {{-- Form panel --}}
        <div class="flex min-h-screen items-center justify-center px-6 py-12 lg:min-h-full">
            <div class="w-full max-w-md">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <img src="{{ asset('images/vyaparhub-logo.png') }}" alt="VyaparHub" class="h-10 w-10 object-contain">
                    <span class="text-lg font-extrabold tracking-tight text-slate-900">VyaparHub</span>
                </div>

                @yield('content')

                <p class="mt-10 text-center text-xs text-slate-400">
                    © {{ date('Y') }} VyaparHub · Inventory · Orders · Profit tracker for Indian sellers
                </p>
            </div>
        </div>
    </div>
</body>
</html>
