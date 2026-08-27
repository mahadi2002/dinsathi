<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\HabitRepo;
use App\Repositories\NotificationRepo;
use App\Repositories\TaskListRepo;
use App\Repositories\TaskRepo;
use App\Repositories\UserRepo;
use App\Services\HabitService;
use App\Support\DateBD;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = (int) $this->currentUserId();
        $user   = (new UserRepo())->find($userId);

        $today    = DateBD::today();
        $tomorrow = (new \DateTimeImmutable($today))->modify('+1 day')->format('Y-m-d');
        $fromUtc  = (string) DateBD::combineToUtc($today, '00:00');
        $toUtc    = (string) DateBD::combineToUtc($tomorrow, '00:00');

        $taskRepo   = new TaskRepo();
        $todayTasks = $taskRepo->dueBetween($userId, $fromUtc, $toUtc);
        $overdue    = $taskRepo->overdueBefore($userId, $fromUtc);
        $counts     = $taskRepo->countBetween($userId, $fromUtc, $toUtc);

        $habitRepo    = new HabitRepo();
        $habitService = new HabitService();
        $habits       = [];
        foreach ($habitRepo->forUser($userId) as $habit) {
            $habits[] = $habit + [
                'streak'      => $habitService->streak((int) $habit['id'], (string) $habit['active_days']),
                'done_today'  => $habitService->isDoneToday((int) $habit['id']),
                'recent_days' => $habitService->recentDays((int) $habit['id'], (string) $habit['active_days'], 7),
            ];
        }

        $lists = (new TaskListRepo())->forUser($userId);
        $notifications = (new NotificationRepo())->recentForUser($userId, 5);

        return $this->view('app/dashboard', [
            'title'         => 'ড্যাশবোর্ড',
            'user'          => $user,
            'today'         => $today,
            'todayTasks'    => $todayTasks,
            'overdueTasks'  => $overdue,
            'doneCount'     => $counts['done'],
            'totalCount'    => $counts['total'],
            'habits'        => $habits,
            'lists'         => $lists,
            'notifications' => $notifications,
        ]);
    }
}
