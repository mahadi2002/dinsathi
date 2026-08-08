<?php
declare(strict_types=1);

/**
 * Plain CLI smoke test, no framework — the "did I just break something
 * load-bearing" gate, not full coverage. Run after touching Crypto, Csrf,
 * Validator, RRuleLite, or DateBD.
 *
 *   php tests/smoke.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

use App\Core\Crypto;
use App\Core\Csrf;
use App\Core\Validator;
use App\Support\DateBD;
use App\Support\RRuleLite;

$failures = 0;
$total = 0;

function check(string $label, bool $condition): void
{
    global $failures, $total;
    $total++;
    if ($condition) {
        echo "  [ok] {$label}\n";
    } else {
        echo "  [FAIL] {$label}\n";
        $failures++;
    }
}

echo "Crypto — AES-256-GCM round-trip\n";
$plain = 'test-value-01712345678';
$cipher = Crypto::encrypt($plain);
check('ciphertext differs from plaintext', $cipher !== $plain);
check('decrypt recovers original', Crypto::decrypt($cipher) === $plain);
check('decrypt rejects garbage', Crypto::decrypt('not-valid-base64-ciphertext') === null);

echo "\nCrypto — blind index (HMAC)\n";
$hash1 = Crypto::blindIndex('01712345678');
$hash2 = Crypto::blindIndex('01712345678');
$hash3 = Crypto::blindIndex('01799999999');
check('stable across calls', $hash1 === $hash2);
check('differs for different input', $hash1 !== $hash3);
check('not reversible to plaintext (no substring leak)', !str_contains($hash1, '01712345678'));

echo "\nCsrf — token comparison\n";
$_SESSION = [];
$token = Csrf::token();
check('token is 64 hex chars', strlen($token) === 64);
check('valid token passes', Csrf::check($token));
check('wrong token fails', !Csrf::check('deadbeef'));
check('empty token fails', !Csrf::check(''));
check('null token fails', !Csrf::check(null));

echo "\nValidator — msisdn rule against real BTRC-style prefixes\n";
foreach (['01712345678', '01612345678', '01912345678'] as $valid) {
    $v = Validator::make(['mobile_number' => $valid], ['mobile_number' => 'required|msisdn']);
    check("accepts {$valid}", $v->passes());
}
foreach (['123', '01712345', '017123456789', 'abcdefghijk'] as $invalid) {
    $v = Validator::make(['mobile_number' => $invalid], ['mobile_number' => 'required|msisdn']);
    check("rejects {$invalid}", $v->fails());
}

echo "\nRRuleLite — recurrence generation\n";
check('DAILY is valid', RRuleLite::isValid('DAILY'));
check('WEEKLY:MO,WE,FR is valid', RRuleLite::isValid('WEEKLY:MO,WE,FR'));
check('MONTHLY:31 is valid', RRuleLite::isValid('MONTHLY:31'));
check('CUSTOM:3 is valid', RRuleLite::isValid('CUSTOM:3'));
check('garbage rule is invalid', !RRuleLite::isValid('EVERY:TUESDAY'));

$daily = RRuleLite::occurrences('DAILY', '2026-01-01', '2026-01-01', 6);
check('DAILY yields 7 occurrences over a 6-day horizon', count($daily) === 7);

$weekly = RRuleLite::occurrences('WEEKLY:MO,WE,FR', '2026-01-01', '2026-01-01', 13);
check('WEEKLY:MO,WE,FR yields only Mon/Wed/Fri dates', array_reduce(
    $weekly,
    static fn($carry, $d) => $carry && in_array((int) date('w', strtotime($d)), [1, 3, 5], true),
    true
));

$monthly = RRuleLite::occurrences('MONTHLY:31', '2026-01-01', '2026-02-01', 30);
check('MONTHLY:31 clamps to Feb 28 on a short month', in_array('2026-02-28', $monthly, true));

echo "\nDateBD — UTC <-> Asia/Dhaka round-trip\n";
$utc = '2026-08-06 12:00:00';
$dhaka = DateBD::toDhaka($utc);
check('UTC 12:00 is Dhaka 18:00 (+6)', $dhaka !== null && $dhaka->format('H:i') === '18:00');
$backToUtc = DateBD::toUtcStorage($dhaka->format('Y-m-d\TH:i'));
check('round-trip returns the original UTC value', $backToUtc === $utc);
check('quiet hours 23:00 is inside 22:00-07:00 window', DateBD::isWithinQuietHours('23:00:00', '22:00:00', '07:00:00'));
check('quiet hours 12:00 is outside 22:00-07:00 window', !DateBD::isWithinQuietHours('12:00:00', '22:00:00', '07:00:00'));

echo "\n" . str_repeat('-', 40) . "\n";
echo "{$total} checks, " . ($total - $failures) . " passed, {$failures} failed.\n";

exit($failures > 0 ? 1 : 0);
