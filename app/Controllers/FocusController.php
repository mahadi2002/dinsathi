<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\FocusSessionRepo;
use App\Repositories\TaskRepo;

final class FocusController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = (int) $this->currentUserId();
        // Capped and nearest-due-first (forList()'s own ordering) so a long-running
        // recurring series doesn't turn the task picker into an unusable wall of options.
        $tasks = array_slice((new TaskRepo())->forList($userId, null, false), 0, 25);

        return $this->view('app/focus', [
            'title'    => 'Focus Timer',
            'sessions' => (new FocusSessionRepo())->recentForUser($userId, 20),
            'tasks'    => $tasks,
        ]);
    }

    public function store(Request $request): Response
    {
        $userId  = (int) $this->currentUserId();
        $seconds = max(60, $request->int('duration_sec'));
        $taskId  = $request->int('task_id') ?: null;

        if ($taskId !== null && (new TaskRepo())->find($taskId, $userId) === null) {
            $taskId = null;
        }

        $endedAt   = Db::nowUtc();
        $startedAt = (new \DateTimeImmutable($endedAt))->modify('-' . $seconds . ' seconds')->format('Y-m-d H:i:s');

        (new FocusSessionRepo())->create($userId, $taskId, $seconds, $startedAt, $endedAt);
        Session::notify('success', 'Focus Session সংরক্ষণ হয়েছে।');

        if ($request->wantsJson()) {
            return $this->json(['ok' => true]);
        }
        return $this->redirect('/app/focus');
    }
}
