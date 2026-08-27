<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\DailyReviewRepo;
use App\Repositories\FocusSessionRepo;
use App\Repositories\HabitRepo;
use App\Repositories\TaskRepo;
use App\Services\HabitService;
use App\Support\DateBD;
use DateTimeImmutable;

/**
 * A personal-stats page: turns the data the app is already collecting
 * (task completions, habit check-ins, focus sessions, daily reviews) into
 * a weekly/monthly summary of what actually got done.
 */
final class InsightsController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = (int) $this->currentUserId();
        $today  = new DateTimeImmutable(DateBD::today());

        $weekStart = $today->modify('monday this week');
        $weekEnd   = $weekStart->modify('+7 days');
        $monthStart = $today->modify('first day of this month');
        $monthEnd   = $monthStart->modify('first day of next month');

        $weekFromUtc  = (string) DateBD::combineToUtc($weekStart->format('Y-m-d'), '00:00');
        $weekToUtc    = (string) DateBD::combineToUtc($weekEnd->format('Y-m-d'), '00:00');
        $monthFromUtc = (string) DateBD::combineToUtc($monthStart->format('Y-m-d'), '00:00');
        $monthToUtc   = (string) DateBD::combineToUtc($monthEnd->format('Y-m-d'), '00:00');

        $taskRepo  = new TaskRepo();
        $weekTasks  = $taskRepo->countBetween($userId, $weekFromUtc, $weekToUtc);
        $monthTasks = $taskRepo->countBetween($userId, $monthFromUtc, $monthToUtc);
        $weekCompletedByDoing = $taskRepo->completedBetween($userId, $weekFromUtc, $weekToUtc);

        $focusRepo = new FocusSessionRepo();
        $weekFocusSec  = $focusRepo->totalSecondsBetween($userId, $weekFromUtc, $weekToUtc);
        $weekFocusCount = $focusRepo->countBetween($userId, $weekFromUtc, $weekToUtc);
        $monthFocusSec = $focusRepo->totalSecondsBetween($userId, $monthFromUtc, $monthToUtc);

        $habitService = new HabitService();
        $habits = array_map(static function (array $h) use ($habitService) {
            return $h + [
                'streak' => $habitService->streak((int) $h['id'], (string) $h['active_days']),
                'recent_days' => $habitService->recentDays((int) $h['id'], (string) $h['active_days'], 14),
            ];
        }, (new HabitRepo())->forUser($userId));

        $bestStreak = array_reduce($habits, static fn($carry, $h) => max($carry, $h['streak']), 0);

        $recentReviews = (new DailyReviewRepo())->recent($userId, 7);

        return $this->view('app/insights', [
            'title'          => 'Insights',
            'today'          => $today->format('Y-m-d'),
            'weekTasksTotal' => $weekTasks['total'],
            'weekTasksDone'  => $weekTasks['done'],
            'weekCompletionRate' => $weekTasks['total'] > 0 ? (int) round(($weekTasks['done'] / $weekTasks['total']) * 100) : null,
            'weekCompletedByDoing' => $weekCompletedByDoing,
            'monthTasksTotal' => $monthTasks['total'],
            'monthTasksDone'  => $monthTasks['done'],
            'weekFocusMinutes'  => (int) round($weekFocusSec / 60),
            'weekFocusCount'    => $weekFocusCount,
            'monthFocusMinutes' => (int) round($monthFocusSec / 60),
            'habits'         => $habits,
            'bestStreak'     => $bestStreak,
            'recentReviews'  => $recentReviews,
            'weekLabel'      => bn_date($weekStart->format('Y-m-d')) . ' — ' . bn_date($weekStart->modify('+6 days')->format('Y-m-d')),
            'monthLabel'     => bn_date($monthStart->format('Y-m-d')),
        ]);
    }
}
