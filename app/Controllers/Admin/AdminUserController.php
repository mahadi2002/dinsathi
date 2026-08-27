<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserRepo;

final class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $page   = max(1, $request->int('page', 1));
        $search = $request->str('q');
        $result = (new UserRepo())->paginate($page, 25, $search);

        return $this->view('admin/users/index', [
            'title'   => 'Users',
            'users'   => $result['data'],
            'total'   => $result['total'],
            'page'    => $page,
            'perPage' => 25,
            'search'  => $search,
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $user = (new UserRepo())->find((int) $id);
        if ($user === null) {
            $this->notFound();
        }

        $taskCount = (int) Db::value('SELECT COUNT(*) FROM tasks WHERE user_id = ? AND is_template = 0', [$id]);
        $habitCount = (int) Db::value('SELECT COUNT(*) FROM habits WHERE user_id = ?', [$id]);

        (new \App\Repositories\AuditLogRepo())->log(
            $this->currentAdminId($request),
            'admin.user.view',
            'user',
            (int) $id
        );

        return $this->view('admin/users/show', [
            'title'      => 'User #' . $id,
            'user'       => $user,
            'taskCount'  => $taskCount,
            'habitCount' => $habitCount,
        ]);
    }

    private function currentAdminId(Request $request): ?int
    {
        return \App\Core\Session::adminId();
    }
}
