<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AuditLogRepo;
use App\Repositories\UserRepo;

final class AdminDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin/dashboard', [
            'title'       => 'Admin Dashboard',
            'totalUsers'  => (new UserRepo())->count(),
            'recentAudit' => (new AuditLogRepo())->recent(15),
        ]);
    }

    public function logs(Request $request): Response
    {
        $file = APP_ROOT . '/storage/logs/app-' . date('Y-m-d') . '.log';
        $tail = is_file($file) ? implode('', array_slice(file($file) ?: [], -200)) : '';

        return $this->view('admin/logs', ['title' => 'Logs', 'tail' => $tail]);
    }
}
