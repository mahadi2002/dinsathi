<?php
declare(strict_types=1);

/**
 * A trivial file-lock so overlapping cron runs (a slow run still going when
 * the next minute fires) can't double-dispatch or double-charge. Each
 * caller gets its own lock file keyed by name.
 */
function cron_lock(string $name): mixed
{
    $dir = APP_ROOT . '/storage/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }

    $handle = fopen($dir . '/' . $name . '.lock', 'c');
    if ($handle === false || !flock($handle, LOCK_EX | LOCK_NB)) {
        return null;
    }

    return $handle;
}

function cron_unlock(mixed $handle): void
{
    if (is_resource($handle)) {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
