<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Hand-written .env parser (~40 lines, zero dependencies).
 * Values live in a static array; nothing is written to $_ENV or getenv().
 */
final class Env
{
    /** @var array<string,string> */
    private static array $vars = [];
    private static bool $loaded = false;

    public static function load(string $file): void
    {
        self::$loaded = true;

        if (!is_readable($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            if ($value !== '' && $value[0] !== '"' && $value[0] !== "'") {
                $value = trim((string) preg_replace('/(^|\s)#.*$/', '', $value));
            }

            $len = strlen($value);
            if ($len >= 2
                && (($value[0] === '"' && $value[$len - 1] === '"')
                    || ($value[0] === "'" && $value[$len - 1] === "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if ($key !== '') {
                self::$vars[$key] = $value;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            return $default;
        }
        if (!array_key_exists($key, self::$vars)) {
            return $default;
        }

        $value = self::$vars[$key];
        return match (strtolower($value)) {
            'true'           => true,
            'false'          => false,
            'null', 'empty'  => null,
            ''               => $default,
            default          => $value,
        };
    }

    public static function has(string $key): bool
    {
        return isset(self::$vars[$key]) && self::$vars[$key] !== '';
    }
}
