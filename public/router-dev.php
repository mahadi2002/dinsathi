<?php
declare(strict_types=1);

/**
 * Router for PHP's built-in development server ONLY.
 *
 *   php -S 127.0.0.1:8000 router-dev.php
 *
 * Apache (via public/.htaccess) and nginx handle this natively in production;
 * this file exists solely so `php -S` behaves the same way — serve a static
 * file if it exists, otherwise hand the request to the front controller.
 */

$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
