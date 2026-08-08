# DinSathi — Environment & Configuration Reference

## 1. Directory Layout (deploy target)

```
/dinsathi
  /public          ← web root (vhost/cPanel document root points HERE, nowhere else)
  /app
  /config
  /storage         ← writable, outside public
  /cron
  .env             ← outside public, never web-accessible
```

Shared-hosting note: if the host only allows `public_html` as document root with no ability to point elsewhere, put everything else **one level above** `public_html` and reference via relative paths — never place `/app`, `/config`, `.env` inside `public_html`.

---

## 2. `.env.example`

```env
APP_ENV=production
APP_URL=https://dinsathi.example.com
APP_KEY=                         # 32-byte random, used for session encryption
APP_TIMEZONE_DISPLAY=Asia/Dhaka

DB_HOST=127.0.0.1
DB_NAME=dinsathi
DB_USER=dinsathi_app
DB_PASS=
DB_CHARSET=utf8mb4

SUBSCRIPTION_GATEWAY=mock        # mock | bdapps
BDAPPS_API_BASE=
BDAPPS_CLIENT_ID=
BDAPPS_CLIENT_SECRET=
BDAPPS_DAILY_AMOUNT=2.78

SMS_GATEWAY=mock                 # mock | provider
SMS_PROVIDER_API_BASE=
SMS_PROVIDER_API_KEY=
SMS_SENDER_ID=DinSathi

WEBPUSH_VAPID_PUBLIC_KEY=
WEBPUSH_VAPID_PRIVATE_KEY=
WEBPUSH_SUBJECT=mailto:support@dinsathi.example.com

SESSION_LIFETIME_MIN=43200       # 30 days
RATE_LIMIT_OTP_PER_HOUR=3
RATE_LIMIT_LOGIN_PER_15MIN=5

CRON_SECRET=                     # shared secret if cron hits an HTTP endpoint instead of CLI
```

---

## 3. `config/gateway.php` — SubscriptionGateway contract

```php
interface SubscriptionGateway {
    public function requestOtp(string $mobileNumber): OtpRequestResult;
    public function confirmSubscription(string $mobileNumber, string $otp): SubscriptionResult;
    public function unsubscribe(string $externalRef): bool;
    public function checkStatus(string $externalRef): SubscriptionStatus;
}
```

- `MockGateway`: always succeeds, writes directly to `subscriptions`/`billing_events`, simulates a 2-second async delay for `checkStatus` polling to mirror real BDApps UX.
- `BdAppsGateway`: stub — throws `NotImplementedException` on every method until real API contract is available. Do not attempt to guess the request/response shape; wire it when the docs arrive.

## 4. `config/sms.php` — SmsGateway contract

```php
interface SmsGateway {
    public function send(string $mobileNumber, string $message): SmsSendResult;
}
```

- `MockSmsGateway`: writes to `sms_log` with `status='sent'`, no external call — lets the whole reminder pipeline be tested end-to-end today.
- `SmsGatewayAdapter`: stub, provider unresolved (see 01-BUILD-SPEC §13). Keep the interface provider-agnostic — constructor takes base URL + API key from `.env` so swapping providers later is a config change, not a code change.

## 5. `config/push.php` — Web Push

- Generate VAPID keypair once (`web-push` CLI or equivalent PHP library — check licensing/Composer-free alternative, or vendor a minimal pure-PHP VAPID+AES-GCM implementation since the project has zero Composer dependencies). Store public key in `public/manifest.webmanifest` reference and `WEBPUSH_VAPID_PUBLIC_KEY`; private key stays server-side only, `.env`, never logged.
- `PushGateway::send()` handles the AES-GCM payload encryption and VAPID JWT signing itself — this is the one place a small amount of crypto code is unavoidable without a dependency; document it heavily and unit-test against known test vectors.

---

## 6. `.htaccess` (public/)

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]

<IfModule mod_headers.c>
  Header always set X-Frame-Options "DENY"
  Header always set X-Content-Type-Options "nosniff"
  Header always set Referrer-Policy "strict-origin-when-cross-origin"
  Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
  Header always set Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; base-uri 'self'; frame-ancestors 'none'"
</IfModule>

# sw.js must be served from root scope with correct MIME
<FilesMatch "sw\.js$">
  Header set Service-Worker-Allowed "/"
</FilesMatch>
```

---

## 7. MySQL Privileges

```sql
CREATE USER 'dinsathi_app'@'localhost' IDENTIFIED BY '<strong-random-password>';
GRANT SELECT, INSERT, UPDATE, DELETE ON dinsathi.* TO 'dinsathi_app'@'localhost';
-- No DDL, no DROP, no GRANT — schema changes run via a separate migration user, never the app account.
FLUSH PRIVILEGES;
```

---

## 8. Cron Setup

```
* * * * * php /path/to/dinsathi/cron/run_jobs.php >> /path/to/dinsathi/storage/logs/cron.log 2>&1
```

`run_jobs.php` dispatches, in order, every minute: `DispatchReminders` → `RetrySmsFailures`. Daily-only jobs (`GenerateRecurringInstances`, `RolloverStreaks`, `CheckSubscriptionExpiry`) are guarded inside their own class by "have I already run today" check against the `jobs` table, so a single per-minute cron entry is enough — no need for separate crontab lines.

---

## 9. Security Checklist (pre-launch)

- [ ] `.env`, `/app`, `/config`, `/storage` unreachable via HTTP (test directly)
- [ ] CSRF token verified on every POST/PATCH/DELETE
- [ ] All queries parameterized — grep for string-concatenated SQL before launch
- [ ] Rate limits active on OTP, login, subscribe endpoints
- [ ] VAPID private key not committed to git, not in any log line
- [ ] Session cookie flags: `Secure`, `HttpOnly`, `SameSite=Lax`
- [ ] Admin panel behind separate auth, IP-allowlist optional but recommended
- [ ] SMS/Push message templates contain no PII beyond task title (already user's own data)
- [ ] Mobile numbers masked in all admin list views, full number only in single-user detail view with audit-logged access
