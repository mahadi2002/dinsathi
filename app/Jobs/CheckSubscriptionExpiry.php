<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Repositories\BillingEventRepo;
use App\Repositories\SubscriptionRepo;
use App\Repositories\UserRepo;

/**
 * Daily — simulates the recurring BDApps charge for every subscription past
 * its next_charge_at. Mirrors MockGateway's dev-testing convention: a
 * mobile number ending 00 simulates low balance (→ suspended), ending 99
 * simulates a hard failure (→ expired), anything else renews. A real
 * BdAppsGateway renewal-webhook path replaces this once BDApps' actual
 * contract is available.
 */
final class CheckSubscriptionExpiry
{
    public function run(): array
    {
        $subRepo  = new SubscriptionRepo();
        $userRepo = new UserRepo();
        $billing  = new BillingEventRepo();
        $stats    = ['renewed' => 0, 'suspended' => 0, 'expired' => 0];

        foreach ($subRepo->dueForCharge() as $sub) {
            $user = $userRepo->find((int) $sub['user_id']);
            if ($user === null) {
                continue;
            }

            $mobile = (string) $user['mobile_number'];
            $amount = (float) $sub['daily_amount'];

            if (str_ends_with($mobile, '99')) {
                $subRepo->setStatus((int) $sub['id'], 'expired');
                $billing->log((int) $sub['id'], 'charge_failed', $amount);
                $stats['expired']++;
            } elseif (str_ends_with($mobile, '00')) {
                $subRepo->setStatus((int) $sub['id'], 'suspended');
                $billing->log((int) $sub['id'], 'charge_failed', $amount);
                $stats['suspended']++;
            } else {
                $subRepo->bumpNextCharge((int) $sub['id']);
                $billing->log((int) $sub['id'], 'charge_success', $amount);
                $stats['renewed']++;
            }
        }

        return $stats;
    }
}
