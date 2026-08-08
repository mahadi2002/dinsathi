<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AuditLogRepo;
use App\Repositories\SmsLogRepo;
use App\Repositories\SubscriptionRepo;
use App\Repositories\UserRepo;

final class AdminDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $subRepo = new SubscriptionRepo();

        $plannerActive = $subRepo->countByStatus('active', 'planner');
        $smsActive     = $subRepo->countByStatus('active', 'sms_reminders');

        $plannerAmount = (float) config('gateway.plans.planner.amount', 2.78);
        $smsAmount     = (float) config('gateway.plans.sms_reminders.amount', 1.00);

        return $this->view('admin/dashboard', [
            'title'          => 'Admin Dashboard',
            'totalUsers'     => (new UserRepo())->count(),
            'plannerActive'  => $plannerActive,
            'plannerSuspended' => $subRepo->countByStatus('suspended', 'planner'),
            'plannerExpired' => $subRepo->countByStatus('expired', 'planner'),
            'smsActive'      => $smsActive,
            'smsSuspended'   => $subRepo->countByStatus('suspended', 'sms_reminders'),
            'smsExpired'     => $subRepo->countByStatus('expired', 'sms_reminders'),
            'mrrProxy'       => ($plannerActive * $plannerAmount + $smsActive * $smsAmount) * 30,
            'smsDeliveryRate' => (new SmsLogRepo())->deliveryRate(),
            'recentAudit'    => (new AuditLogRepo())->recent(15),
        ]);
    }

    public function logs(Request $request): Response
    {
        $file = APP_ROOT . '/storage/logs/app-' . date('Y-m-d') . '.log';
        $tail = is_file($file) ? implode('', array_slice(file($file) ?: [], -200)) : '';

        return $this->view('admin/logs', ['title' => 'Logs', 'tail' => $tail]);
    }
}
