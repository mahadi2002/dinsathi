<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $files,
        public readonly array $headers,
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST' && isset($_POST['_method'])) {
            $spoof = strtoupper((string) $_POST['_method']);
            if (in_array($spoof, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $spoof;
            }
        }

        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $path = '/' . trim(rawurldecode($path), '/');

        return new self(
            $method,
            $path === '//' ? '/' : $path,
            $_GET,
            $_POST,
            $_FILES,
            self::readHeaders(),
        );
    }

    private static function readHeaders(): array
    {
        $out = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $out[strtolower(str_replace('_', '-', substr($k, 5)))] = (string) $v;
            }
        }
        foreach (['CONTENT_TYPE' => 'content-type', 'CONTENT_LENGTH' => 'content-length'] as $k => $n) {
            if (isset($_SERVER[$k])) {
                $out[$n] = (string) $_SERVER[$k];
            }
        }
        return $out;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    /** The raw parsed POST body — Validator::make() and controllers read from here. */
    public function body(): array
    {
        return $this->body;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function str(string $key, string $default = ''): string
    {
        $v = $this->input($key, $default);
        return is_scalar($v) ? trim((string) $v) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->input($key);
        return is_numeric($v) ? (int) $v : $default;
    }

    public function bool(string $key): bool
    {
        $v = $this->input($key);
        return $v === '1' || $v === 1 || $v === 'on' || $v === 'true' || $v === true;
    }

    /** @return string[] */
    public function arr(string $key): array
    {
        $v = $this->input($key, []);
        if (!is_array($v)) {
            return [];
        }
        return array_values(array_filter(array_map(
            static fn($x) => is_scalar($x) ? (string) $x : null,
            $v
        )));
    }

    public function file(string $key): ?array
    {
        $f = $this->files[$key] ?? null;
        if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $f;
    }

    public function rawBody(): string
    {
        return (string) file_get_contents('php://input');
    }

    public function json(): array
    {
        $decoded = json_decode($this->rawBody(), true);
        return is_array($decoded) ? $decoded : [];
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * Client IP. Only trusts X-Forwarded-For when the immediate peer is in
     * TRUSTED_PROXIES — otherwise the header is trivially spoofed and would
     * defeat every IP-keyed rate limit.
     */
    public function ip(): string
    {
        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $trusted = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))));

        if ($trusted && in_array($remote, $trusted, true)) {
            $fwd = $this->header('x-forwarded-for');
            if ($fwd) {
                $first = trim(explode(',', $fwd)[0]);
                if (filter_var($first, FILTER_VALIDATE_IP)) {
                    return $first;
                }
            }
        }
        return $remote;
    }

    public function userAgent(): string
    {
        return substr((string) $this->header('user-agent', ''), 0, 512);
    }

    public function ipHash(): string
    {
        return Crypto::blindIndex('ip:' . $this->ip());
    }

    public function uaHash(): string
    {
        return Crypto::blindIndex('ua:' . $this->userAgent());
    }

    public function wantsJson(): bool
    {
        return str_contains((string) $this->header('accept', ''), 'application/json');
    }
}
