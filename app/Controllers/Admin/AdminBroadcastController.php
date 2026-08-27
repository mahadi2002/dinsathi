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

final class AdminBroadcastController extends Controller
{
    public function form(Request $request): Response
    {
        return $this->view('admin/broadcast', ['title' => 'Broadcast']);
    }

    /** In-app + push announcement to every user. */
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

        $reached = (new NotificationRepo())->createForAll('broadcast', $v->get('title'), $v->get('message'));

        (new AuditLogRepo())->log(Session::adminId(), 'admin.broadcast', null, null, [
            'title' => $v->get('title'), 'reached' => $reached,
        ]);

        Session::notify('success', bn_num($reached) . ' জন ব্যবহারকারীকে Notification পাঠানো হয়েছে।');
        return $this->redirect('/admin/broadcast');
    }
}
