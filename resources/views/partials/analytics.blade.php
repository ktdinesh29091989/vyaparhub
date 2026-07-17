{{-- Public-page visit tracking. Inert until PLAUSIBLE_DOMAIN is set in .env (see .env.example). --}}
@if (config('services.plausible.domain'))
    <script defer data-domain="{{ config('services.plausible.domain') }}" src="https://plausible.io/js/script.js"></script>
@endif
