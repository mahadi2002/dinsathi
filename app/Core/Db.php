<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use Throwable;

/**
 * PDO singleton. Prepared statements only — no string interpolation into SQL,
 * ever, including ORDER BY (sort columns come from a fixed allowlist).
 * Connection stays in UTC — all storage is UTC per the locked decision;
 * Asia/Dhaka conversion happens only at the view layer via Support\DateBD.
 */
final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $c   = config('database');
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $c['host'],
            $c['port'],
            $c['name'],
            $c['charset']
        );

        self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);

        self::$pdo->exec("SET time_zone = '+00:00'");

        return self::$pdo;
    }

    /** Inject a connection (migrate.php uses the DDL-capable user). */
    public static function setPdo(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function first(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }

    /** @return int affected rows */
    public static function exec(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    /**
     * Run a closure inside a transaction. Rolls back and rethrows on failure.
     *
     * @template T
     * @param  callable():T $fn
     * @return T
     */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $own = !$pdo->inTransaction();

        if ($own) {
            $pdo->beginTransaction();
        }

        try {
            $result = $fn($pdo);
            if ($own) {
                $pdo->commit();
            }
            return $result;
        } catch (Throwable $e) {
            if ($own && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Build a positional IN(...) placeholder list. The caller merges $values
     * into its parameter array in the same order.
     */
    public static function placeholders(array $values): string
    {
        return implode(',', array_fill(0, max(1, count($values)), '?'));
    }

    public static function nowUtc(): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}
