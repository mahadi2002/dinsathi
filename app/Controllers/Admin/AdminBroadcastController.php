<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\AuditLogRepo;
use App\Repositories\NotificationRepo;
use App\Repositories\SmsLogRepo;
use App\Repositories\UserRepo;
use App\Gateways\GatewayFactory;
use App\Core\Db;

final class AdminBroadcastController extends Controller
{
    public function form(Request $request): Response
    {
        return $this->view('admin/broadcast', ['title' => 'Broadcast']);
    }

    /** Push + SMS announcement to every subscribed user, via the same gateways the reminder pipeline uses. */
    public function send(Request $request): Response
    {
        $v = Validator::make($request->body(), [
            'title'   => 'required|max:150',
            'message' => 'required|max:200',
        ], ['title' => 'শিরোনাম', 'message' => 'বার্তা']);

        if ($v->fails()) {
            $v->flash();
            return $this->redirect('/admin/broadcast');
        }

        $reached = (new NotificationRepo())->createForAllSubscribed('broadcast', $v->get('title'), $v->get('message'));

        $smsSent = 0;
        if ($request->bool('also_sms')) {
            $userRepo = new UserRepo();
            $smsRepo  = new SmsLogRepo();
            $mobiles  = Db::all(
                "SELECT DISTINCT u.mobile_number FROM users u JOIN subscriptions s ON s.user_id = u.id
                 WHERE s.status = 'active' AND u.sms_reminders_on = 1"
            );
            foreach ($mobiles as $row) {
                $result = GatewayFactory::sms()->send((string) $row['mobile_number'], $v->get('message'));
                $smsRepo->create(null, (string) $row['mobile_number'], $v->get('message'), 'mock', $result['success'] ? 'sent' : 'failed');
                $smsSent++;
            }
        }

        (new AuditLogRepo())->log(\App\Core\Session::adminId(), 'admin.broadcast', null, null, [
            'title' => $v->get('title'), 'reached' => $reached, 'sms_sent' => $smsSent,
        ]);

        Session::notify('success', bn_num($reached) . " জন Subscriber-কে Notification পাঠানো হয়েছে" . ($smsSent > 0 ? " (SMS: " . bn_num($smsSent) . ")" : '') . "।");
        return $this->redirect('/admin/broadcast');
    }
}
