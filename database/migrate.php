<?php
declare(strict_types=1);

/**
 * CLI migration + seed runner.
 *
 *   php database/migrate.php            apply pending migrations
 *   php database/migrate.php --seed     apply migrations, then run seeds
 *   php database/migrate.php --status   list applied / pending
 *   php database/migrate.php --fresh    DROP the whole schema and rebuild (dev only)
 *
 * Uses DB_MIGRATE_USER / DB_MIGRATE_PASS when present — the runtime app user
 * has no DDL rights in production, so a SQL-injection bug cannot drop tables.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

use App\Core\Db;

$args   = array_slice($argv, 1);
$seed   = in_array('--seed', $args, true);
$status = in_array('--status', $args, true);
$fresh  = in_array('--fresh', $args, true);

$cfg = config('database');
$dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $cfg['host'], $cfg['port'], $cfg['charset']);

$pdo = new PDO($dsn, (string) $cfg['migrate_user'], (string) $cfg['migrate_pass'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

$dbName = (string) $cfg['name'];

if ($fresh) {
    if (config('app.env') === 'production') {
        fwrite(STDERR, "--fresh is refused when APP_ENV=production.\n");
        exit(1);
    }
    out("Dropping database `$dbName` …");
    $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
}

$pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$dbName`");
$pdo->exec("SET time_zone = '+00:00'");

Db::setPdo($pdo);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
        filename   VARCHAR(160) NOT NULL,
        applied_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_mig (filename)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$applied = array_column($pdo->query('SELECT filename FROM migrations')->fetchAll(), 'filename');

$files = glob(APP_ROOT . '/database/migrations/*.sql') ?: [];
sort($files, SORT_STRING);

if ($status) {
    out("Migrations for `$dbName`:");
    foreach ($files as $file) {
        $name = basename($file);
        out(sprintf('  [%s] %s', in_array($name, $applied, true) ? 'x' : ' ', $name));
    }
    exit(0);
}

$ran = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        continue;
    }

    out("→ $name");

    try {
        foreach (splitStatements((string) file_get_contents($file)) as $sql) {
            $pdo->exec($sql);
        }

        $stmt = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
        $stmt->execute([$name]);
        $ran++;
    } catch (PDOException $e) {
        fwrite(STDERR, "\nFAILED in $name:\n  " . $e->getMessage() . "\n");
        exit(1);
    }
}

out($ran === 0 ? 'Nothing to migrate — schema is up to date.' : "Applied $ran migration(s).");

if ($seed) {
    $seedFiles = glob(APP_ROOT . '/database/seeds/*.sql') ?: [];
    sort($seedFiles, SORT_STRING);

    out("\nSeeding …");
    foreach ($seedFiles as $file) {
        out('→ ' . basename($file));
        foreach (splitStatements((string) file_get_contents($file)) as $sql) {
            $pdo->exec($sql);
        }
    }

    foreach (glob(APP_ROOT . '/database/seeds/*.php') ?: [] as $file) {
        out('→ ' . basename($file));
        require $file;
    }

    out('Seeding complete.');
}

exit(0);

// ── helpers ─────────────────────────────────────────────────────────────

function out(string $line): void
{
    fwrite(STDOUT, $line . PHP_EOL);
}

/**
 * Split a .sql file into statements on semicolons that are not inside a
 * string literal or a comment.
 *
 * @return string[]
 */
function splitStatements(string $sql): array
{
    $statements = [];
    $buffer     = '';
    $len        = strlen($sql);
    $inSingle   = false;
    $inDouble   = false;
    $inBacktick = false;
    $inComment  = false;
    $inBlock    = false;

    for ($i = 0; $i < $len; $i++) {
        $ch   = $sql[$i];
        $next = $sql[$i + 1] ?? '';

        if ($inComment) {
            if ($ch === "\n") {
                $inComment = false;
                $buffer .= $ch;
            }
            continue;
        }

        if ($inBlock) {
            if ($ch === '*' && $next === '/') {
                $inBlock = false;
                $i++;
            }
            continue;
        }

        if (!$inSingle && !$inDouble && !$inBacktick) {
            if (($ch === '-' && $next === '-') || $ch === '#') {
                $inComment = true;
                continue;
            }
            if ($ch === '/' && $next === '*') {
                $inBlock = true;
                $i++;
                continue;
            }
        }

        if ($ch === "'" && !$inDouble && !$inBacktick && ($sql[$i - 1] ?? '') !== '\\') {
            $inSingle = !$inSingle;
        } elseif ($ch === '"' && !$inSingle && !$inBacktick && ($sql[$i - 1] ?? '') !== '\\') {
            $inDouble = !$inDouble;
        } elseif ($ch === '`' && !$inSingle && !$inDouble) {
            $inBacktick = !$inBacktick;
        }

        if ($ch === ';' && !$inSingle && !$inDouble && !$inBacktick) {
            $trimmed = trim($buffer);
            if ($trimmed !== '') {
                $statements[] = $trimmed;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $ch;
    }

    $trimmed = trim($buffer);
    if ($trimmed !== '') {
        $statements[] = $trimmed;
    }

    return $statements;
}
