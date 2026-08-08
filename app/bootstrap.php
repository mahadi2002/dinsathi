<?php
declare(strict_types=1);

/**
 * Single entry point for every execution context: the web front controller,
 * the CLI cron scripts, and database/migrate.php.
 *
 * APP_ROOT must be defined by the caller before this file is required.
 */

if (!defined('APP_ROOT')) {
    throw new RuntimeException('APP_ROOT must be defined before bootstrap.php.');
}

// ── Autoloader (PSR-4: App\ → app/) ─────────────────────────────────────
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $path = APP_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

require_once APP_ROOT . '/app/Core/Env.php';
require_once APP_ROOT . '/app/Core/Helpers.php';

// ── Environment ─────────────────────────────────────────────────────────
App\Core\Env::load(APP_ROOT . '/.env');

// Process timezone stays UTC (storage decision); Asia/Dhaka conversion is
// explicit at the view layer via utc_to_dhaka()/dhaka_to_utc() so no code
// path can accidentally rely on the ambient zone.
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

$isDebug = (bool) config('app.debug');
$env     = (string) config('app.env', 'production');

ini_set('display_errors', $isDebug && PHP_SAPI === 'cli' ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ── Startup guards — fail loud here rather than silently misbehave later ─
$fatal = static function (string $message): never {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'FATAL: ' . $message . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Configuration error. See storage/logs.';
    error_log('[bootstrap] ' . $message);
    exit(1);
};

if (!App\Core\Env::has('APP_KEY') || !App\Core\Env::has('HASH_PEPPER')) {
    $fatal('APP_KEY and HASH_PEPPER must be set in .env. Generate with: php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"');
}

if (strlen((string) config('app.key')) !== 32 || strlen((string) config('app.pepper')) !== 32) {
    $fatal('APP_KEY and HASH_PEPPER must each decode to exactly 32 bytes of base64.');
}

if ($env === 'production') {
    if ($isDebug) {
        $fatal('APP_DEBUG must be false when APP_ENV=production.');
    }
    if (config('gateway.driver') === 'mock') {
        $fatal('SUBSCRIPTION_GATEWAY=mock is blocked when APP_ENV=production.');
    }
}

// ── Storage directories ────────────────────────────────────────────────
foreach (['logs', 'cache', 'uploads', 'backups'] as $dir) {
    $path = APP_ROOT . '/storage/' . $dir;
    if (!is_dir($path)) {
        @mkdir($path, 0750, true);
    }
}

// ── Error handling ─────────────────────────────────────────────────────
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $e): void {
    App\Core\Logger::exception($e);

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, get_class($e) . ': ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL);
        exit(1);
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    if (config('app.debug')) {
        echo '<pre>' . e(get_class($e) . ': ' . $e->getMessage() . "\n\n" . $e->getTraceAsString()) . '</pre>';
        return;
    }

    try {
        echo App\Core\View::render('errors/500');
    } catch (Throwable) {
        echo 'সাময়িক সমস্যা হচ্ছে। একটু পরে আবার চেষ্টা করুন।';
    }
});

register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        App\Core\Logger::error('Fatal: ' . $err['message'], ['file' => $err['file'] . ':' . $err['line']]);
    }
});

// ── Values every view can rely on ──────────────────────────────────────
App\Core\View::share('appName', (string) config('app.name'));
App\Core\View::share('dailyAmount', number_format((float) config('gateway.plans.planner.amount', 2.78), 2));
App\Core\View::share('smsAmount', number_format((float) config('gateway.plans.sms_reminders.amount', 1.00), 2));
App\Core\View::share('shortcode', (string) config('gateway.shortcode'));
App\Core\View::share('operatorNote', (string) config('operators.display_note'));
