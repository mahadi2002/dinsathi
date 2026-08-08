<?php
declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;

/**
 * Matches [method, path, 'Controller@action', [middleware]] entries from
 * app/routes.php, runs the middleware pipeline, then dispatches.
 */
final class Router
{
    /** @var array<string,class-string> */
    private const MIDDLEWARE = [
        'csrf'  => \App\Middleware\CsrfGuard::class,
        'guest' => \App\Middleware\GuestOnly::class,
        'auth'  => \App\Middleware\RequireAuth::class,
        'sub'   => \App\Middleware\RequireSubscription::class,
        'admin' => \App\Middleware\RequireAdmin::class,
        'rl'    => \App\Middleware\RateLimiter::class,
    ];

    public function __construct(private array $routes)
    {
    }

    public function dispatch(Request $request): Response
    {
        $allowedMethods = [];

        foreach ($this->routes as [$method, $path, $handler, $middleware]) {
            $pattern = $this->compile($path);
            if (!preg_match($pattern, $request->path, $m)) {
                continue;
            }

            if (strtoupper($method) !== $request->method) {
                $allowedMethods[] = strtoupper($method);
                continue;
            }

            $params = array_values(array_filter(
                $m,
                static fn($k) => is_string($k),
                ARRAY_FILTER_USE_KEY
            ));

            return $this->runPipeline($request, $middleware, fn() => $this->call($handler, $request, $params));
        }

        if ($allowedMethods !== []) {
            throw new HttpException(405, 'Method Not Allowed');
        }

        throw new HttpException(404);
    }

    /** `/app/tasks/{id}` → `#^/app/tasks/(?P<p0>\d{1,19})$#`; also supports {slug} and {date} */
    private function compile(string $path): string
    {
        static $cache = [];
        if (isset($cache[$path])) {
            return $cache[$path];
        }

        $parts = preg_split('/(\{(?:slug|id|date)\})/', $path, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $regex = '';
        $i     = 0;

        foreach ($parts as $part) {
            $regex .= match ($part) {
                '{slug}' => '(?P<p' . $i++ . '>[a-z0-9\-]{1,140})',
                '{id}'   => '(?P<p' . $i++ . '>\d{1,19})',
                '{date}' => '(?P<p' . $i++ . '>\d{4}-\d{2}-\d{2})',
                default  => preg_quote($part, '#'),
            };
        }

        return $cache[$path] = '#^' . $regex . '$#u';
    }

    private function runPipeline(Request $request, array $middleware, callable $core): Response
    {
        $next = $core;

        foreach (array_reverse($middleware) as $key) {
            $arg  = null;
            $name = $key;
            if (str_contains($key, ':')) {
                [$name, $arg] = explode(':', $key, 2);
            }

            $class = self::MIDDLEWARE[$name] ?? null;
            if ($class === null) {
                throw new \RuntimeException('Unknown middleware: ' . $name);
            }

            $current = $next;
            $next = static function () use ($class, $arg, $request, $current): Response {
                /** @var \App\Middleware\Middleware $instance */
                $instance = $arg === null ? new $class() : new $class($arg);
                return $instance->handle($request, $current);
            };
        }

        return $next();
    }

    private function call(string $handler, Request $request, array $params): Response
    {
        [$controller, $action] = explode('@', $handler, 2);
        $class = 'App\\Controllers\\' . str_replace('/', '\\', $controller);

        if (!class_exists($class) || !method_exists($class, $action)) {
            throw new \RuntimeException('Handler not callable: ' . $handler);
        }

        return (new $class())->{$action}($request, ...$params);
    }
}
