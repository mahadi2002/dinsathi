<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\SmsLogRepo;

final class AdminSmsController extends Controller
{
    public function index(Request $request): Response
    {
        $page   = max(1, $request->int('page', 1));
        $result = (new SmsLogRepo())->paginate($page, 30);

        return $this->view('admin/sms-log', [
            'title'        => 'SMS Delivery Log',
            'logs'         => $result['data'],
            'total'        => $result['total'],
            'page'         => $page,
            'perPage'      => 30,
            'deliveryRate' => (new SmsLogRepo())->deliveryRate(),
        ]);
    }
}
