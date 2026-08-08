<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\BillingEventRepo;
use App\Repositories\SubscriptionRepo;

/**
 * Two independent subscriptions, no free tier on either: 'planner' unlocks
 * the app itself, 'sms_reminders' is a separate paid add-on for the SMS
 * delivery channel. Every gated request re-reads from the DB — no cached
 * session flag ever decides access, hasAccess()/hasSmsAccess() are queries,
 * not getters.
 */
final class SubscriptionService
{
    public const PLAN_PLANNER = 'planner';
    public const PLAN_SMS     = 'sms_reminders';

    /** The base subscription — required for every planner feature, no exceptions. */
    public static function hasAccess(int $userId): bool
    {
        return (new SubscriptionRepo())->findActive($userId, self::PLAN_PLANNER) !== null;
    }

    /** The SMS add-on — required on top of hasAccess() before any SMS actually sends. */
    public static function hasSmsAccess(int $userId): bool
    {
        return (new SubscriptionRepo())->findActive($userId, self::PLAN_SMS) !== null;
    }

    public static function current(int $userId, string $plan = self::PLAN_PLANNER): ?array
    {
        return (new SubscriptionRepo())->latest($userId, $plan);
    }

    /** Both plans' latest rows at once — settings/dashboard read this instead of two round trips. */
    public static function currentBoth(int $userId): array
    {
        return (new SubscriptionRepo())->latestBoth($userId);
    }

    public static function activate(
        int $userId,
        string $plan,
        string $gateway,
        float $amount,
        ?string $externalRef,
        string $initialStatus = 'active'
    ): array {
        $repo = new SubscriptionRepo();
        $id   = $repo->create($userId, $plan, $gateway, $amount, $externalRef);

        if ($initialStatus !== 'active') {
            $repo->setStatus($id, $initialStatus);
        }

        (new BillingEventRepo())->log($id, 'subscribe', $amount, ['external_ref' => $externalRef, 'plan' => $plan]);

        return (array) $repo->latest($userId, $plan);
    }

    /** Reachable from every subscription state, including one that never activated. */
    public static function unsubscribe(int $userId, string $plan = self::PLAN_PLANNER): void
    {
        $repo = new SubscriptionRepo();
        $sub  = $repo->latest($userId, $plan);
        if ($sub === null || $sub['status'] === 'unsubscribed') {
            return;
        }

        $repo->setStatus((int) $sub['id'], 'unsubscribed');
        (new BillingEventRepo())->log((int) $sub['id'], 'unsubscribe', null, ['plan' => $plan]);

        // Server-side revocation: an already-open browser loses access on its
        // very next request, not at next login. Unsubscribing from either
        // plan re-checks the whole session, since losing 'planner' means
        // losing everything gated behind it.
        Session::revokeAllForUser($userId);
    }
}
