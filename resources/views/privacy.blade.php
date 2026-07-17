<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy · VyaparHub</title>
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

    <header class="border-b border-slate-100">
        <div class="mx-auto flex h-16 max-w-3xl items-center px-4 sm:px-6">
            <a href="{{ route('landing') }}" class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-lg text-white">🧵</div>
                <span class="text-lg font-extrabold tracking-tight text-slate-900">VyaparHub</span>
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <h1 class="text-3xl font-extrabold text-slate-900">Privacy Policy</h1>
        <p class="mt-2 text-sm text-slate-500">Last updated: {{ date('d M Y') }}</p>

        <div class="mt-8 space-y-8 text-sm leading-relaxed text-slate-700">
            <section>
                <h2 class="text-lg font-bold text-slate-900">What data we collect</h2>
                <p class="mt-2">When you create a VyaparHub account and use the app, we collect the information you give us directly, including:</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    <li>Your name, business name, email address and mobile number</li>
                    <li>Business data you enter — products, stock levels, orders, customers, expenses and channel details</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">How we use it</h2>
                <p class="mt-2">We use your data only to provide the VyaparHub service to you — running your dashboard, calculating stock and profit reports, sending account-related emails (such as email verification), and providing customer support. We do not use your business data for any purpose beyond operating the service you signed up for.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">We do not sell data</h2>
                <p class="mt-2">We do not sell, rent or trade your personal or business data to any third party for marketing or any other purpose.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">Payments</h2>
                <p class="mt-2">All subscription payments are processed by Razorpay. VyaparHub does not collect or store your card, UPI or bank details — Razorpay handles all payment data directly under its own security and compliance standards. We only store the subscription status and payment reference needed to activate your plan.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">How to delete your account</h2>
                <p class="mt-2">To request deletion of your account and all associated data, email us at the address below. We will process your request and confirm once your data has been removed.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">Contact</h2>
                <p class="mt-2">Questions about this policy or your data? Email us at
                    <a href="mailto:support@vyaparhub.in" class="font-medium text-brand-600 hover:text-brand-700">support@vyaparhub.in</a>.
                </p>
            </section>
        </div>

        <a href="{{ route('landing') }}" class="mt-12 inline-block text-sm font-medium text-brand-600 hover:text-brand-700">← Back to home</a>
    </main>

</body>
</html>
