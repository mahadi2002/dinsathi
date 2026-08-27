# Security

## Auth

Email + password for regular users (`AuthController`) — passwords are
hashed with PHP's `password_hash()`/`PASSWORD_DEFAULT` (bcrypt) and checked
with `password_verify()`. Login always runs `password_verify()` against a
real bcrypt hash — a hard-coded dummy hash stands in when the email doesn't
exist — so a non-existent account takes about as long to reject as a wrong
password. Registration and login are both rate-limited by IP hash
(`rl:register`, `rl:login`); a duplicate-email registration and any login
failure both show the same generic message, never confirming which
addresses are already registered.

Password reset: a random token (`Crypto::randomToken(32)`) is emailed
(`MailerService::sendPasswordReset()`) and never stored raw — only its
`Crypto::blindIndex()` (HMAC-SHA256, keyed by `HASH_PEPPER`) goes in
`password_resets.token_hash`, with a TTL (`PASSWORD_RESET_TTL_SECONDS`,
1 hour by default) and a `consumed_at` flag so a link only works once.
Completing a reset revokes every other session on that account
(`Session::revokeAllForUser()`).

Admin auth is **entirely separate**: email + password (`password_hash`,
bcrypt via PHP's default), its own session flag (`admin_id`), its own
rate-limit bucket (`rl:admin_login`), optional `ADMIN_IP_ALLOWLIST`.

## Sessions

DB-backed (`sessions` table), never files — this is what lets a suspended
or deleted account, or a password change, kill every other open session on
its *very next request* instead of at its next login. Bound to a hash of
the user-agent (not IP — mobile data IPs rotate constantly; IP-binding
would just log people out randomly). Regenerated on login
(`Session::regenerate()`). Killed server-side by
`Session::revokeAllForUser()` on password reset
(`AuthController::resetPassword()`) and on account status change
(`RequireAuth` middleware finds a non-`active` account on any gated request
and revokes + destroys the session on the spot).

## Access control

Every `/app/*` route requires only the `auth` middleware (`RequireAuth`) —
a signed-in account in `active` status, nothing else. There is no paid
tier, no free/paid split, and no separate gate for reminders: Web Push is
bundled for every account. Admin routes are gated the same way by a
parallel `admin` middleware (`RequireAdmin`) checking `admin_id`, entirely
separate from user auth.

## CSRF

Token on every state-changing POST/PATCH/DELETE (`CsrfGuard` middleware +
`csrf_field()` in every form) — no exceptions in the current route table
(see `app/routes.php`); there's no webhook or other unauthenticated
state-changing endpoint that would need one.

## Rate limiting

`rate_limits` table, fixed-window, keyed by IP hash: registration, login,
password-reset request, admin login, contact form, CSV export
(`rl:register`, `rl:login`, `rl:password_reset`, `rl:admin_login`,
`rl:contact`, `rl:export` in `app/routes.php`). Each bucket is independent,
so hitting one doesn't burn down another.

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

Regular users are identified by `email` + `password_hash` only — no phone
number is collected or stored since the Phase 1 email+password rebuild
(`users.mobile_number`/`operator` were dropped by
`010_phase1_email_auth_and_billing_removal.sql`). Passwords and other
secrets are never logged: `Logger::scrub()` redacts any context key named
`password`, `token`, `app_key`, `secret`, `payload`, `auth_key`, or
`p256dh_key` before a line is written. Admin user list/detail views show
the account's email directly — there's no masking helper, since email
isn't treated as sensitively as the phone number was under the old scheme.

## Headers (`SecurityHeaders` middleware + `.htaccess`)

`X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
`Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`
denying geolocation/camera/mic/payment, HSTS (skipped on localhost),
`X-Powered-By` unset. Applied globally, including to error pages.

## Pre-launch checklist

`.env`/`/app`/`/config`/`/storage` unreachable via HTTP, VAPID private key
never committed or logged, session cookie flags (`Secure`/`HttpOnly`/
`SameSite=Lax`), admin password changed from the seeded default
(`ChangeMe123!`), no password ever appears in `storage/logs/*.log` (verify
against `Logger::scrub()`'s blocklist).
