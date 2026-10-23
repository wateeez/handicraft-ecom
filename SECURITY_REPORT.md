# Security Report — handicraft-ecom

- **Date:** 2026-10-23
- **Branch:** ecom2
- **Method:** Static code review (grep sweeps plus manual reading of the flagged code). No dynamic scan (HawkScan/DAST) was run and no exploit was attempted.
- **Scope:** `app/`, `routes/`, `resources/views/`, `bootstrap/`, `.env` settings (secrets not printed).
- **Limits:** Findings were not confirmed by running the app. A clean result for a category means "nothing found by this review", not a guarantee.

## Summary

| # | Area | Result | Severity |
|---|------|--------|----------|
| 1 | PHP runtime patch level | **Fail**: outdated | High |
| 2 | SQL injection | Pass: no injectable queries found | Info |
| 3 | Cross-site scripting (XSS) | Mostly pass: 1 weak spot, 1 HTML-injection issue | Low–Medium |
| 4 | CSRF protection | Pass for web routes; **API routes are unauthenticated** | Medium |
| 5 | File upload validation | Partial: image uploads OK; **bulk ZIP import and SVG uploads weak** | Medium–High |
| 6 | SSRF | Pass: no user-controlled outbound URLs | Info |
| 7 | Auth, config and other | Login not rate-limited; `APP_DEBUG=true`; mass assignment | Medium |
| 8 | Compromise indicators | No evidence of break-in found | Info |

**Highest priorities:** (1) update PHP, (2) lock down the unauthenticated PayPal and order endpoints, (3) whitelist file extensions in the bulk image ZIP import and drop SVG from logo uploads, (4) rate-limit login, (5) set `APP_DEBUG=false` in production.

---

## Remediation status (2026-10-23)

Fixed in code (uncommitted; review with `git diff`):

| Item | Change |
|---|---|
| Unauthenticated PayPal/cancel endpoints | New per-order HMAC `pay_token` (`Order::paymentToken()`), issued by `checkout.init-order`, required by `createOrder`, `capturePayment` and `cancelPending`. Checkout JS sends it. PayPal order ID validated against `^[A-Za-z0-9]{10,40}$` |
| No API rate limiting | `throttleApi()` in `bootstrap/app.php`; `throttle:10,1` on the three PayPal routes |
| Login / public forms unthrottled | `throttle:5,1` on `POST /login`; `throttle:10,1` on inquiry, quote, init-order, calculate-shipping; `throttle:60,1` on cart routes |
| **New:** public `/register` route | `Auth::routes()` registered open registration, and self-registered accounts reach admin read routes. Removed; only explicit login/logout remain. This also fixes the `route:list` crash (missing `ForgotPasswordController`) |
| Inquiry email HTML injection | `name`, product name and sender name now escaped with `e()` |
| SVG logo uploads | `svg` removed from allowed types (png/jpg/gif/webp, plus ico for the favicon) |
| Bulk ZIP import | Entry-count and uncompressed-size caps, path-traversal check, unique 0700 temp dir, real-image check via `finfo` + `getimagesize`, extension taken from detected MIME type |
| Mass assignment (shipping rates) | `only([...])` instead of `all()`; zone/provider IDs validated with `exists` |
| Unescaped JSON in `<script>` | `JSON_HEX_TAG\|AMP\|APOS\|QUOT` on the order-edit page; `JSON_HEX_TAG\|AMP` on the JSON-LD blocks |
| Guessable success page | `/checkout/success/{orderNumber}` now requires the `token` query parameter |
| Session cookie | `secure` defaults to true when `APP_ENV=production` |
| Forex URL editable? | Checked: the admin form only saves validated rate values, not URLs. No change needed |

Verified: lint clean on all changed files; Blade views compile; wrong or missing `pay_token` returns 422/404 on all three PayPal routes; the API throttle returns 429 after 10 requests a minute; `/register` returns 404; `POST /login` carries `throttle:5,1`.

Not verified: a full PayPal checkout end to end (needs sandbox credentials and a browser), and the login throttle under a real request (CSRF intercepts first when tested from the console). Database-backed tests cannot run here because PHP lacks the `pdo_sqlite` driver.

**Still open (not code changes):**
1. Upgrade PHP on every environment (after a backup). Confirm the fixed versions on php.net.
2. Set `APP_DEBUG=false` and `APP_ENV=production` on the live server.
3. Review the 4 admin accounts (names, roles, last-login IPs) in the admin panel.
4. Run a dynamic scan (OWASP ZAP / HawkScan) on a staging copy.
5. Longer term: move models from `$guarded = []` to `$fillable`, and move routing into `bootstrap/app.php`.

Old `checkout/success` links without the token stop working. Customers already redirected there keep the page for that visit only.

---

## 1. PHP runtime patch level — HIGH

| Install | Version found | Patched release named in the advisory email |
|---|---|---|
| System `php` | 8.3.6 | 8.3.36 |
| XAMPP `/opt/lampp/bin/php` | 8.2.12 (built Nov 2023) | 8.2.35 |

- The project requires `php ^8.2` (`composer.json`), so a patched 8.2/8.3/8.4 build is compatible.
- The advisory email was a mass mailing with no CVE IDs or links. **Confirm the fixed versions on php.net** before relying on it. Updating from XAMPP's 8.2.12 is justified either way, because it is missing years of security fixes.
- **Fix:** back up the DB and files, then upgrade PHP on every environment, production first.

## 2. SQL injection — PASS

All raw SQL sites were reviewed:

| Location | Finding |
|---|---|
| `FinanceController.php:37,42,78,102`, `FinanceDashboardService.php:94`, `FinanceInsightService.php:36,61` | `selectRaw` with static column expressions only, no request input |
| `HomeController.php:93,96` | `orderByRaw('COALESCE(discount_price, price) asc/desc')` is chosen by a `switch` on `$request->get('sort')`. The input only picks between fixed strings, so it is not injectable |
| `ShippingController.php:576` | `whereRaw('LOWER(name) = ?', [...])` is parameterised |
| Product search (`HomeController`) | Uses `like` through the query builder with bindings |

- No `DB::statement`, `DB::unprepared`, or string-concatenated SQL found.
- Everything else uses Eloquent or the query builder, which bind parameters.

## 3. Cross-site scripting (XSS) — LOW–MEDIUM

Blade `{{ }}` escapes by default. All 15 `{!! !!}` sites were reviewed:

| File | Verdict |
|---|---|
| `clients/show.blade.php:55`, `layouts/app.blade.php:185`, `emails/.../admin-inquiry-alert.blade.php:111`, `products/show.blade.php:349`, `shipping-policy.blade.php:22`, `blog/show.blade.php:129` | Safe: `nl2br(e(...))` escapes first |
| `layouts/app.blade.php:35`, `products/show.blade.php:24`, `categories/show.blade.php:37,47` | JSON-LD via `json_encode` inside `<script type="application/ld+json">`. Mostly safe. Add `JSON_HEX_TAG` so a value containing `</script>` cannot break out |
| `components/finance/bars.blade.php:46,48` | Labels pass through `e()`. `$color` and `$item['href']` are server-built. Safe as long as they never take user input |
| `orders/show.blade.php:642` | `action_icon` returns fixed emoji from a `match`. Safe |
| `orders/edit.blade.php:389` | **Weak.** `buildAlpineArray` runs `json_encode` on data that includes the user-supplied `product_name`, printed unescaped inside `<script>`. A name containing `</script><script>…` would break out. `str_replace("'", "\'", …)` does not help. The names come from the product catalogue, which only admins can edit, so exposure is low. Fix: `{{ Js::from($arr) }}` or `json_encode($arr, JSON_HEX_TAG\|JSON_HEX_AMP\|JSON_HEX_APOS\|JSON_HEX_QUOT)`, and remove the manual `str_replace` |

**Related — HTML injection in email (`Frontend/InquiryController.php`, store()):**
the confirmation email is built with `Mail::html("Dear {$request->name}, … {$product->name}")`. `name` is attacker-supplied and **not escaped**, and `email` is also attacker-supplied. Anyone can make your mail server send an HTML email, containing their own links, to any address, from your sender identity. This is a phishing and spam-relay risk. Fix: wrap interpolated values in `e()`, or use a Blade mailable. Also add throttling (see §7).

## 4. CSRF protection — PASS for web routes, GAP on API

- `bootstrap/app.php` has **no CSRF exclusions**. Laravel's default `VerifyCsrfToken` protection for the `web` group is active.
- Every `<form method="post|put|patch|delete">` in `resources/views` includes `@csrf`; the scan found none without it.
- State-changing admin actions (status changes, mark-paid, cancel, payouts, settings, user and role changes) are all POST/PUT/DELETE routes, not GET.
- Session cookie settings were not overridden in `.env`; `SESSION_ENCRYPT=false`. Confirm `http_only=true`, `same_site=lax` and `secure=true` (HTTPS) in `config/session.php` for production.

**Gap — `routes/api.php` is outside CSRF and has no authentication:**
```
POST /api/paypal/orders
POST /api/paypal/orders/{orderId}/capture
POST /api/orders/{order}/cancel-pending
```
(The file's own comment calls them "Postman test routes".)
- `cancelPending(Order $order)` binds a **sequential integer ID** and cancels any unpaid order with no ownership or token check. Anyone can loop over IDs and cancel every pending customer order. **Medium–High, trivially exploitable.**
- `createOrder`/`capturePayment` take `order_id` from the body with the same weakness (IDOR). The amount is read from the DB, which is good, but the caller is not tied to the order.
- **Fix:** require a per-order unguessable token (for example the existing `checkout_token`) or the session that created the order. Remove "test" routes from production. Add `throttle` middleware.

## 5. File upload validation — PARTIAL

| Upload | Validation | Verdict |
|---|---|---|
| Product `main_image`, `images.*` | `nullable\|image\|max:2048` | OK. `image` rule checks content. Note that Laravel 11+ excludes SVG from `image` by default |
| Blog `featured_image` | `nullable\|image\|max:2048` | OK |
| Site logos, favicon, QR | `image\|mimes:…,svg\|max:…` | **SVG allowed.** SVGs can carry `<script>`. Served from the public disk, an uploaded SVG is stored XSS for anyone who opens the file URL directly. Remove `svg` or sanitise it |
| Shipping rates / zones CSV, product CSV | `file\|mimes:csv,txt` | OK (parsed as data, not stored) |
| Bulk image ZIP (`ProductController` ~L495–590) | `file\|mimes:zip\|max:51200` | **Weak.** See below |

**Bulk ZIP import weaknesses (admin-only, but high impact if an admin account or the file is compromised):**
1. Extracted files are never checked for type. The extension comes from the archive entry name (`pathinfo`) and is reused: `'products/' . uniqid() . '.' . $extension`. A ZIP entry such as `SKU.php`, `.html` or `.svg` for an existing SKU is written to the public media disk with that extension. With a local `public` disk that is a path to stored XSS and, on a misconfigured server, remote code execution.
2. No limit on entry count or uncompressed size (zip-bomb). The 50 MB limit applies to the compressed file only.
3. Temp dir `storage/app/temp-images-{time()}` is predictable, not unique.
4. `ZipArchive::extractTo` in current PHP rejects `../` path traversal, but there is no explicit check. Add one.

**Fix:** whitelist `jpg, jpeg, png, webp, gif`; confirm content with `getimagesize()`/`finfo`; generate the stored extension from the detected MIME type, not the filename; cap entries and uncompressed size; use a unique temp dir and clean it up.

Other upload notes: filenames use Laravel's hashed `store()` names (good). Original client filenames are not used for storage in the standard paths.

## 6. SSRF — PASS

All server-side HTTP requests:

| Location | Target | Verdict |
|---|---|---|
| `NrbForexRateProvider.php:45` | `config('services.forex.nrb_url')` | Fixed config URL |
| `ApiForexRateProvider.php:49` | `config('services.forex.url' / 'open_url')` | Fixed config URL, no user input |
| `PayPalController.php` | `api-m.paypal.com` / `api-m.sandbox.paypal.com`, plus a `{$paypalOrderId}` path segment | Hard-coded host. The ID comes from the route and is placed in the path only. Validate it against `^[A-Z0-9]+$` to avoid path tricks |
| `ResendNotificationService.php` | Guzzle client to the Resend API | Fixed endpoint |
| `ProductController.php:590` | `file_get_contents($filePath)` | Local extracted temp file, not a URL |

- No endpoint fetches a URL supplied by a user, no webhooks to user-defined URLs, no image-by-URL import.
- Config URLs live in `.env`/`config`, so they are only a risk if an attacker can edit those. The forex rate settings are editable from the admin panel (`FinanceRateSettingController`) — confirm they cannot change the **URL**, only the rate values.

## 7. Authentication, configuration and other findings

| Finding | Severity | Detail |
|---|---|---|
| Login not rate-limited | Medium | `LoginController::login` calls `Auth::attempt` with no `throttle` middleware or `RateLimiter`. Only an `api` limiter exists. Brute-force and credential stuffing are possible. Add `throttle:5,1` or `RateLimiter` on `/login` |
| Public forms not throttled | Medium | `/products/{product}/inquire`, `/checkout/submit-quote`, `/cart/*` can be abused for spam and for the email issue in §3 |
| `APP_DEBUG=true`, `APP_ENV=local` | High if in production | Exposes stack traces, env values and queries. Must be `false` / `production` on the live server (this finding is for the local `.env`; verify production's) |
| Mass assignment | Medium | `ShippingController::storeRate/updateRate` pass `$request->all()` to models with `$guarded = []` (`ShippingRate` etc.). Validation covers only some fields, so extra fields can be written. Use `$request->validated()` / `only([...])`. `$guarded = []` is also set on `Product`, `Category`, `BlogPost`, `Inquiry`, `PaymentMethod`, `ShippingZone/Provider`, `OrderFinanceSnapshot`. Prefer explicit `$fillable` |
| Guessable order numbers | Low | `orderSuccess/{orderNumber}` shows paid-order details (client, items) to anyone who knows or guesses the number. Use an unguessable token |
| Session | Low | `SESSION_ENCRYPT=false`; database driver is fine |
| `.env` committed? | OK | `.env` is gitignored (verified) |
| Dangerous functions | OK | No `eval`, `exec`, `shell_exec`, `system`, `passthru`, `unserialize` in `app/` or `routes/` |
| Authorisation | OK (not fully tested) | Admin routes use `role` / `permission` / `super_admin` middleware aliases. Per-route coverage was not exhaustively verified |
| Open redirects | OK | No `redirect($request->…)` patterns found |

## 8. Compromise indicators (from the PHP advisory)

- No `.php` files outside `vendor` modified in the last 3 days.
- Only uncommitted change: `routes/web.php` (already modified before the audit).
- `users` table has 4 accounts: ID 1 created 2026-10-10 18:12:42, IDs 2–4 created 20 seconds later, none modified since. This is consistent with a seeder and shows no new admin accounts.
- **Not checked:** account names and emails, role assignments, `last_login_at`/`last_login_ip`. Review these in the admin panel for anything unfamiliar.

## Recommended fix order

1. **Upgrade PHP** (all environments, production first) after taking a backup.
2. **Protect the API routes** — token or session check on `cancel-pending`, `createOrder`, `capture`; add throttling.
3. **Harden the bulk image ZIP import** — extension and MIME whitelist, size and entry limits, unique temp dir.
4. **Remove SVG from logo uploads**, or sanitise it.
5. **Throttle `/login`** and the public inquiry and quote forms.
6. **Escape the inquiry confirmation email** (`e($request->name)`) and review other `Mail::html` calls.
7. **Set `APP_DEBUG=false`, `APP_ENV=production`** on the live server.
8. Replace `$request->all()` with validated data in the shipping controller; move models from `$guarded = []` to `$fillable`.
9. Use `JSON_HEX_TAG` / `Js::from` in the order-edit and JSON-LD script blocks.
10. Run a dynamic scan (for example HawkScan/OWASP ZAP) against a staging copy to confirm these findings and catch what static review misses.
