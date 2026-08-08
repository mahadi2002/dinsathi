<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\BillingEventRepo;

final class AdminBillingController extends Controller
{
    public function index(Request $request): Response
    {
        $page   = max(1, $request->int('page', 1));
        $result = (new BillingEventRepo())->paginate($page, 30);

        return $this->view('admin/billing', [
            'title'   => 'Billing Events',
            'events'  => $result['data'],
            'total'   => $result['total'],
            'page'    => $page,
            'perPage' => 30,
        ]);
    }
}
