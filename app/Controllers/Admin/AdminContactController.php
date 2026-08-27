<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\ContactMessageRepo;

final class AdminContactController extends Controller
{
    public function index(Request $request): Response
    {
        $page   = max(1, $request->int('page', 1));
        $status = $request->str('status');
        $status = in_array($status, ['new', 'resolved'], true) ? $status : null;

        $repo   = new ContactMessageRepo();
        $result = $repo->paginate($page, 25, $status);

        return $this->view('admin/contact', [
            'title'    => 'Contact Inbox',
            'messages' => $result['data'],
            'total'    => $result['total'],
            'page'     => $page,
            'perPage'  => 25,
            'status'   => $status,
            'newCount' => $repo->countNew(),
        ]);
    }

    public function resolve(Request $request, string $id): Response
    {
        $ok = (new ContactMessageRepo())->markResolved((int) $id);
        if (!$ok) {
            $this->notFound();
        }

        Session::notify('success', 'বার্তাটি সমাধান হিসেবে চিহ্নিত হয়েছে।');
        return $this->back($request, '/admin/contact');
    }
}
