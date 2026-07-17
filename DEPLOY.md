# VyaparHub — Production Deployment Checklist

Target: shared/VPS hosting with a `public/` document root (e.g. Hostinger). Laravel 12, SQLite in dev / MySQL recommended in production.

## 1. Server setup

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate            # only if APP_KEY is empty in the server .env
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

- `--no-dev` skips dev-only packages (Pest, Faker, etc.) — smaller, faster autoloader.
- `config:cache` / `route:cache` / `view:cache` must be **re-run after every deploy** that changes `.env`, routes, or Blade files. If you forget after changing `.env`, Laravel will keep serving the old cached config.
- `migrate --force` is required because `artisan migrate` refuses to run against a non-local `APP_ENV` without `--force`.

### File permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache   # adjust user:group to your host's PHP-FPM user
```

`storage/` and `bootstrap/cache/` must be writable by the web server user (logs, compiled views, sessions, cache). Nothing else in the app needs write access.

## 2. Environment variables (`.env` on the server)

Copy `.env.example` to `.env` on the server, then fill in real values. Every variable the app actually reads:

| Variable | Required | Description |
|---|---|---|
| `APP_NAME` | Yes | `VyaparHub` — used in email "from name", footer branding. |
| `APP_ENV` | Yes | `production` |
| `APP_KEY` | Yes | Generate with `php artisan key:generate`. Never reuse the dev key. |
| `APP_DEBUG` | Yes | `false` — **must never be `true` in production** (leaks stack traces, env values, and file paths to visitors on error pages). |
| `APP_URL` | Yes | Your real domain, e.g. `https://vyaparhub.in`. Used for generated links (password reset, email verification, Razorpay redirect origin). |
| `APP_TIMEZONE` | Yes | `Asia/Kolkata` — every timestamp (`created_at`, order/stock history times, "today" boundaries on the dashboard & reports) is stored and displayed in this timezone. Change this only if the business itself moves to another timezone; a config change alone won't retroactively fix already-stored timestamps (see the `shift_existing_timestamps_from_utc_to_ist` migration for how that was handled once). |
| `DB_CONNECTION` | Yes | `mysql` on most shared hosts (Hostinger). SQLite is dev-only. |
| `DB_HOST` | Yes (if mysql) | Usually `127.0.0.1` or `localhost` on Hostinger. |
| `DB_PORT` | Yes (if mysql) | `3306` |
| `DB_DATABASE` | Yes (if mysql) | Database name created in hPanel. |
| `DB_USERNAME` | Yes (if mysql) | Database user created in hPanel. |
| `DB_PASSWORD` | Yes (if mysql) | Database user password. |
| `SESSION_DRIVER` | Yes | `database` (sessions table already migrated). |
| `SESSION_LIFETIME` | No | Defaults to `120` minutes — fine as-is. |
| `CACHE_STORE` | Yes | `database` (cache table already migrated). |
| `QUEUE_CONNECTION` | Yes | `database` (jobs table already migrated). No queue worker is required today — nothing currently dispatches queued jobs — but keep this set for when one is added. |
| `MAIL_MAILER` | Yes | `smtp` |
| `MAIL_HOST` | Yes | `smtp.gmail.com` |
| `MAIL_PORT` | Yes | `587` |
| `MAIL_USERNAME` | Yes | Your Gmail address. |
| `MAIL_PASSWORD` | Yes | 16-character **Gmail App Password** (not your normal Gmail password — see §5). |
| `MAIL_FROM_ADDRESS` | Yes | Same Gmail address. |
| `MAIL_FROM_NAME` | Yes | `VyaparHub` |
| `RAZORPAY_KEY_ID` | Yes | **Live** key (`rzp_live_...`) — see §3. |
| `RAZORPAY_KEY_SECRET` | Yes | Live secret — keep out of git, out of logs. |
| `RAZORPAY_WEBHOOK_SECRET` | Yes | The secret you set when creating the live webhook (see §4). Must match exactly. |
| `LOG_LEVEL` | No | `error` in production is usually enough; `debug` is fine too, it just writes more to `storage/logs/laravel.log`. |
| `BCRYPT_ROUNDS` | No | `12` (default) is fine. |

Not used by this app (leftover Laravel skeleton vars) — safe to leave blank: `AWS_*`, `POSTMARK_API_KEY`, `RESEND_API_KEY`, `SLACK_*`.

## 3. Razorpay: test → live

1. In the Razorpay Dashboard, switch from **Test Mode** to **Live Mode** (top-left toggle) — this requires KYC/business verification to be completed first.
2. Settings → API Keys → generate a **Live** key pair. Set `RAZORPAY_KEY_ID` / `RAZORPAY_KEY_SECRET` in the server `.env` to the live values (`rzp_live_...`).
3. Razorpay requires a **Privacy Policy URL** for live activation — use `https://yourdomain.com/privacy` (already built, see below).
4. Re-run `php artisan config:cache` after updating `.env` so the new keys take effect.

## 4. Razorpay webhook (live)

1. Razorpay Dashboard → Settings → Webhooks → **Add New Webhook**.
2. Webhook URL: `https://yourdomain.com/razorpay/webhook`
3. Active events: `payment.captured` (activates Pro) and `payment.failed` (logged for the admin conversion view — never activates anything).
4. Set a webhook secret and put the same value in `RAZORPAY_WEBHOOK_SECRET` on the server — `SubscriptionController::webhook()` verifies every incoming call against this secret and rejects anything that doesn't match (`app/Http/Controllers/SubscriptionController.php`). This route is intentionally CSRF-exempt (`bootstrap/app.php`) since Razorpay can't send a CSRF token — signature verification is what protects it instead.
5. Send a test webhook from the Razorpay dashboard and confirm it returns `{"status":"ok"}`.

## 5. Hostinger-specific notes

- **Document root must point to `public/`**, not the project root. In hPanel → Websites → Advanced → set the document root to `.../saas/public`. If your plan doesn't let you change the document root, symlink or move the contents of `public/` up and adjust `index.php`'s `require` paths instead — pointing the domain straight at the project root will expose `.env`, `app/`, `vendor/`, etc.
- Use PHP 8.2+ (check hPanel → PHP Configuration) to match this project's `composer.json` requirement.
- SSH access is needed to run `composer install` and `artisan` commands — if your plan only offers File Manager, use Hostinger's built-in "Setup" for Laravel or contact support to enable SSH.

### Gmail SMTP app password setup

1. Go to your Google Account → **Security**.
2. Enable **2-Step Verification** if it isn't already on (App Passwords require it).
3. Search for **App Passwords** (Security → 2-Step Verification → App passwords, or go directly to https://myaccount.google.com/apppasswords).
4. Create a new app password — name it "VyaparHub" — choose app type "Mail".
5. Google shows a 16-character password (spaces don't matter). Copy it into `MAIL_PASSWORD` in `.env` — **not** your normal Gmail login password.
6. Test with `php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('you@example.com')->subject('test'));` and confirm it arrives.

## 6. Demo account (optional, for prospects to try)

```bash
php artisan db:seed --class=DemoSeeder
```

Creates `demo@vyaparhub.in` / `demo123` with an active Pro plan, 8 realistic Salem-textile products, and 15 orders (across Meesho/WhatsApp/Local/Amazon, including 3 returns) so a visitor sees a fully populated dashboard immediately. Safe to re-run — it's idempotent (won't duplicate products or orders on a second run).

## 6b. Admin access

Grant yourself (or anyone) access to `/admin` — no separate login, it's the same account with an `is_admin` flag:

```bash
railway run php artisan admin:promote you@example.com
```

Prompts for confirmation before applying; add `--force` to skip the prompt, or `--revoke` to remove admin access. Safe to re-run — it's a no-op if the target state is already reached.

## 7. Post-deploy smoke test

- [ ] `https://yourdomain.com/` shows the landing page (logged out).
- [ ] Register a real account → verification email arrives via Gmail SMTP, branded "VyaparHub".
- [ ] Log in → redirected to `/dashboard`; visiting `/` while logged in redirects to `/dashboard`.
- [ ] `/upgrade` → Razorpay live checkout opens, complete a real ₹299 payment, confirm plan flips to Pro and `plan_expires_at` is ~30 days out.
- [ ] Trigger a test webhook from the Razorpay dashboard → `200 {"status":"ok"}`.
- [ ] `/privacy` loads.
- [ ] `storage/logs/laravel.log` is **not** reachable at `https://yourdomain.com/storage/logs/laravel.log` (404 expected).

## 8. Security checklist (confirmed at time of writing)

- [x] `APP_DEBUG=false` in `.env.example` (production template) — never `true` on a live server.
- [x] `.env` is listed in `.gitignore` — never committed.
- [x] `/razorpay/webhook` verifies the Razorpay signature before doing anything with the payload (`SubscriptionController::webhook`), and is the *only* CSRF-exempt route (`bootstrap/app.php`).
- [x] Every non-public route (products, orders, reports, billing, profile) sits inside the `auth` middleware group in `routes/web.php`; Pro-gated routes additionally carry `plan.gate`.
- [x] All state-changing Blade forms include `@csrf`.
- [x] No `dd()`, `var_dump()`, or `console.log()` debug statements in `app/`, `resources/`, or `routes/`.
- [x] `storage/logs` sits outside `public/` (default Laravel layout) — not web-accessible.
