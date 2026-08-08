# Security

## Auth

Phone + OTP only, no passwords, for regular users — matches the BDApps SDP
flow and the spec's locked decision (01-BUILD-SPEC.md §11). OTP codes are
never stored in plaintext: `OtpService` hashes with `Crypto::blindIndex()`
(HMAC-SHA256, keyed by `HASH_PEPPER`) and compares with `hash_equals()`.
5-minute TTL, capped attempts, rate-limited per mobile number.

Admin auth is **entirely separate**: email + password (`password_hash`,
bcrypt/argon2id via PHP's default), its own session flag (`admin_id`), its
own rate-limit bucket, optional `ADMIN_IP_ALLOWLIST`.

## Sessions

DB-backed (`sessions` table), never files — the only way a lapsed
subscription can lose access on another device's *very next request*
instead of at its next login. Bound to a hash of the user-agent (not IP —
mobile carrier IPs rotate constantly in this market; IP-binding would just
log people out randomly). Regenerated on login. Killed server-side by
`Session::revokeAllForUser()` on unsubscribe and on account status change.

## Subscription gate

`RequireSubscription` middleware and `SubscriptionService::hasAccess()` are
one code path, always a live DB query. No session flag, no cookie, no
cached claim ever decides access — verified by hand in this build's browser
walkthrough (unsubscribe → reload `/app` → redirected to `/subscribe`, same
request cycle). There is no free tier: every `/app/*` route requires an
active `planner` subscription; `hasSmsAccess()` is the same live-query
pattern for the separate `sms_reminders` add-on.

## CSRF

Token on every state-changing POST/PATCH/DELETE (`CsrfGuard` middleware +
`csrf_field()` in every form). The one exception in the route table is a
future billing webhook, which can't carry a session token and would be
verified by signature + IP allowlist instead — not yet wired since the real
BDApps webhook contract doesn't exist yet.

## Rate limiting

`rate_limits` table, fixed-window, keyed by IP hash (+ user id where
relevant): OTP request/verify/resend, login, admin login, contact form,
CSV export. IP-only limiting is deliberately avoided for anything an
attacker could weaponize into a shared-network lockout.

## Content-Security-Policy

`default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'
data:; ...` — no `unsafe-inline` anywhere, on either scripts or styles.
Concretely:

- Every dynamic "would normally be `style=""`" value is a pre-generated CSS
  utility class instead (`.progress-N` conic-gradient steps for the
  day-progress ring, `.swatch-1`..`.swatch-8` for the fixed list-color
  palette, `.mt-sm`/`.narrow-md`/etc. for one-off spacing).
- Confirm-before-destructive-action uses `data-confirm="…"` read by a
  listener in `app.js`, never `onsubmit="return confirm(...)"`.
- Any data JS needs from PHP travels through `<script type="application/json">`
  and `JSON.parse`, never an inline `<script>` block with interpolated data
  (see the push-notification config block in `views/layouts/app.php`).

## Input / output

- **SQL**: prepared statements everywhere (`Db::run()`), `PDO::ATTR_EMULATE_PREPARES`
  off. No string-built SQL, including `ORDER BY`.
- **Output**: every echoed value goes through `e()` (htmlspecialchars,
  `ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5`). There is no rich-text/Markdown
  content type in this app (unlike sibling apps' plant/word/food guides),
  so there's no whitelist-renderer surface to worry about.
- **Validation**: `App\Core\Validator` — raw `$_POST`/`$_GET` is never used
  directly in a controller.

## PII handling

`mobile_number` is stored in plain `VARCHAR(11)` per DinSathi's own locked
schema (see docs/DATABASE.md for why this diverges from the workspace's
usual two-secret encrypt+blind-index pattern). It is never logged in full —
`Logger::scrub()` redacts any context key named `mobile_number`/`msisdn`/
`phone`/`otp`/etc., and gateway classes log only the last 4 digits. Admin
list views show `mask_msisdn()` output (`017XXXXX78`); the full number
appears only on the single-user detail page, and that view is
audit-logged.

## Headers (`SecurityHeaders` middleware + `.htaccess`)

`X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
`Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`
denying geolocation/camera/mic/payment, HSTS (skipped on localhost),
`X-Powered-By` unset. Applied globally, including to error pages.

## Pre-launch checklist

See `03-ENV-AND-CONFIG.md §9` (this app's own spec) — `.env`/`/app`/
`/config`/`/storage` unreachable via HTTP, VAPID private key never
committed or logged, session cookie flags (`Secure`/`HttpOnly`/`SameSite=Lax`),
admin password changed from the seeded default, mobile numbers masked
everywhere except the audited detail view.
