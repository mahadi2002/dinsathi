<?php
declare(strict_types=1);

/**
 * Global helper functions. Loaded once by bootstrap.php before anything else.
 */

if (!function_exists('e')) {
    /** The mandatory escape helper. Every echo of a variable goes through this. */
    function e(?string $v): string
    {
        return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('env')) {
    /** Read a value from the parsed .env. Never call getenv() elsewhere. */
    function env(string $key, mixed $default = null): mixed
    {
        return \App\Core\Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    /** config('app.debug') — dot path into config/*.php */
    function config(string $path, mixed $default = null): mixed
    {
        static $cache = [];
        $parts = explode('.', $path);
        $file  = array_shift($parts);

        if (!array_key_exists($file, $cache)) {
            $full = APP_ROOT . '/config/' . $file . '.php';
            $cache[$file] = is_file($full) ? require $full : [];
        }

        $value = $cache[$file];
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }
}

if (!function_exists('url')) {
    /** Absolute URL for a path within the app. */
    function url(string $path = '/'): string
    {
        $base = (string) config('app.url', '');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /** Cache-busted asset URL. Assets are cached for a year, so the mtime matters. */
    function asset(string $path): string
    {
        $path = '/assets/' . ltrim($path, '/');
        $file = APP_ROOT . '/public' . $path;
        $ver  = is_file($file) ? (string) filemtime($file) : '1';
        return $path . '?v=' . $ver;
    }
}

if (!function_exists('bn_num')) {
    /**
     * Latin digits → Bangla digits. Use for counts and dates only.
     * Prices (৳2.78) and phone numbers stay in Latin digits — that's how
     * people actually read money and phone numbers here, regardless of
     * which digits the rest of the sentence is in.
     */
    function bn_num(int|float|string $n): string
    {
        return strtr((string) $n, [
            '0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪',
            '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯',
        ]);
    }
}

if (!function_exists('bn_date')) {
    /** Format a date/datetime string (assumed Asia/Dhaka already) in Bangla digits. */
    function bn_date(?string $datetime, bool $withTime = false): string
    {
        if (!$datetime) {
            return '—';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return '—';
        }
        $months = [
            1 => 'জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন',
            'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর',
        ];
        $out = bn_num(date('d', $ts)) . ' ' . $months[(int) date('n', $ts)] . ' ' . bn_num(date('Y', $ts));
        if ($withTime) {
            $out .= ', ' . bn_num(date('h:i', $ts)) . ' ' . (date('a', $ts) === 'am' ? 'পূর্বাহ্ণ' : 'অপরাহ্ণ');
        }
        return $out;
    }
}

if (!function_exists('bn_date_utc')) {
    /**
     * Format a UTC-stored DATETIME column (due_at, created_at, started_at, ...)
     * as Bangla-digit Asia/Dhaka wall-clock time. This is the one function
     * views should reach for on any *_at column — bn_date() alone assumes
     * its input is already Dhaka-local (calendar DATE columns, URL date
     * params) and would silently show the raw UTC hour otherwise.
     */
    function bn_date_utc(?string $utc, bool $withTime = false): string
    {
        $dhaka = \App\Support\DateBD::toDhaka($utc);
        return $dhaka === null ? '—' : bn_date($dhaka->format('Y-m-d H:i:s'), $withTime);
    }
}

if (!function_exists('dhaka_now')) {
    /** Current Asia/Dhaka datetime string, used to convert a UTC column at the view layer. */
    function dhaka_now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('Asia/Dhaka'));
    }
}

if (!function_exists('utc_to_dhaka')) {
    /** A UTC 'Y-m-d H:i:s' string (as stored) → Asia/Dhaka DateTimeImmutable. */
    function utc_to_dhaka(?string $utc): ?DateTimeImmutable
    {
        if (!$utc) {
            return null;
        }
        try {
            return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Dhaka'));
        } catch (Exception) {
            return null;
        }
    }
}

if (!function_exists('dhaka_to_utc')) {
    /** A local Asia/Dhaka 'Y-m-d H:i' string from a datetime-local input → UTC 'Y-m-d H:i:s' for storage. */
    function dhaka_to_utc(?string $local): ?string
    {
        if (!$local) {
            return null;
        }
        try {
            $dt = new DateTimeImmutable($local, new DateTimeZone('Asia/Dhaka'));
            return $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } catch (Exception) {
            return null;
        }
    }
}

if (!function_exists('old')) {
    /** Re-populate a form field after a validation failure. */
    function old(string $key, string $default = ''): string
    {
        $old = \App\Core\Session::flashGet('_old') ?? [];
        return (string) ($old[$key] ?? $default);
    }
}

if (!function_exists('errors')) {
    /** Validation errors flashed from the previous request. */
    function errors(): array
    {
        return \App\Core\Session::flashGet('_errors') ?? [];
    }
}

if (!function_exists('error_for')) {
    function error_for(string $field): ?string
    {
        $all = errors();
        return isset($all[$field]) ? (string) $all[$field][0] : null;
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(\App\Core\Csrf::token()) . '">';
    }
}

if (!function_exists('str_excerpt')) {
    /** Multibyte-safe excerpt that does not cut a Bangla word in half. */
    function str_excerpt(?string $text, int $chars = 180): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)) ?? '');
        if (mb_strlen($text, 'UTF-8') <= $chars) {
            return $text;
        }
        $cut = mb_substr($text, 0, $chars, 'UTF-8');
        $sp  = mb_strrpos($cut, ' ', 0, 'UTF-8');
        return rtrim($sp !== false ? mb_substr($cut, 0, $sp, 'UTF-8') : $cut, ' ,।-') . '…';
    }
}

if (!function_exists('pct_step')) {
    /**
     * Round a 0-100 value to the nearest 5 for the .w-pct-N / .progress-N
     * utility classes — the CSP has no unsafe-inline, so a dynamic percentage
     * can never be written as style="width:N%".
     */
    function pct_step(float $value, float $max = 100.0): int
    {
        $pct = $max > 0 ? ($value / $max) * 100 : 0.0;
        $pct = max(0.0, min(100.0, $pct));
        return (int) (round($pct / 5) * 5);
    }
}

if (!function_exists('list_color_class')) {
    /**
     * A user's chosen list/tag color maps to one of a fixed 9-swatch CSS
     * palette (.swatch-0..8 in app.css) instead of an inline style="" — the
     * <select> in views only ever offers config('planner.list_colors'), so
     * this is a finite lookup, not arbitrary hex reaching the page.
     */
    function list_color_class(?string $hex): string
    {
        $palette = (array) config('planner.list_colors', []);
        $index = array_search($hex, $palette, true);
        return 'swatch-' . ($index === false ? 0 : $index + 1);
    }
}

if (!function_exists('mask_msisdn')) {
    /** 01712345678 → 017XXXXX78 — the only form a phone number takes in admin list views. */
    function mask_msisdn(string $msisdn): string
    {
        if (strlen($msisdn) !== 11) {
            return $msisdn;
        }
        return substr($msisdn, 0, 3) . 'XXXXX' . substr($msisdn, -2);
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        if (!config('app.debug')) {
            http_response_code(500);
            exit;
        }
        header('Content-Type: text/plain; charset=utf-8');
        foreach ($vars as $v) {
            var_dump($v);
        }
        exit;
    }
}
