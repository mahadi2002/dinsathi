<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\TaskListRepo;
use App\Repositories\TaskRepo;
use App\Support\DateBD;
use DateTimeImmutable;

final class CalendarController extends Controller
{
    public function day(Request $request, string $date): Response
    {
        $userId = (int) $this->currentUserId();
        $date   = $this->safeDate($date);

        $fromUtc = (string) DateBD::combineToUtc($date, '00:00');
        $toUtc   = (string) DateBD::combineToUtc((new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d'), '00:00');

        $taskRepo = new TaskRepo();

        return $this->view('app/calendar-day', [
            'title'      => 'দিন ভিউ',
            'date'       => $date,
            'tasks'      => $taskRepo->dueBetween($userId, $fromUtc, $toUtc),
            'lists'      => (new TaskListRepo())->forUser($userId),
            'prevDate'   => (new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d'),
            'nextDate'   => (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d'),
        ]);
    }

    public function week(Request $request, string $date): Response
    {
        $userId = (int) $this->currentUserId();
        $date   = $this->safeDate($date);

        $anchor = new DateTimeImmutable($date);
        $monday = $anchor->modify('monday this week');
        $sunday = $monday->modify('+6 days');

        $fromUtc = (string) DateBD::combineToUtc($monday->format('Y-m-d'), '00:00');
        $toUtc   = (string) DateBD::combineToUtc($sunday->modify('+1 day')->format('Y-m-d'), '00:00');

        $tasks = (new TaskRepo())->dueBetween($userId, $fromUtc, $toUtc);

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $monday->modify("+{$i} day")->format('Y-m-d');
            $days[$d] = [];
        }
        foreach ($tasks as $task) {
            $d = DateBD::toDhaka((string) $task['due_at'])?->format('Y-m-d');
            if ($d !== null && isset($days[$d])) {
                $days[$d][] = $task;
            }
        }

        return $this->view('app/calendar-week', [
            'title'    => 'সপ্তাহ ভিউ',
            'monday'   => $monday->format('Y-m-d'),
            'days'     => $days,
            'prevDate' => $monday->modify('-7 days')->format('Y-m-d'),
            'nextDate' => $monday->modify('+7 days')->format('Y-m-d'),
        ]);
    }

    public function month(Request $request, string $date): Response
    {
        $userId = (int) $this->currentUserId();
        $date   = $this->safeDate($date);

        $anchor    = new DateTimeImmutable($date);
        $firstDay  = $anchor->modify('first day of this month');
        $lastDay   = $anchor->modify('last day of this month');
        $gridStart = $firstDay->modify('monday this week');
        $gridEnd   = $lastDay->modify('sunday this week');

        $fromUtc = (string) DateBD::combineToUtc($gridStart->format('Y-m-d'), '00:00');
        $toUtc   = (string) DateBD::combineToUtc($gridEnd->modify('+1 day')->format('Y-m-d'), '00:00');

        $tasks = (new TaskRepo())->dueBetween($userId, $fromUtc, $toUtc);

        $byDay = [];
        foreach ($tasks as $task) {
            $d = DateBD::toDhaka((string) $task['due_at'])?->format('Y-m-d');
            if ($d !== null) {
                $byDay[$d][] = $task;
            }
        }

        $weeks = [];
        $cursor = $gridStart;
        while ($cursor <= $gridEnd) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $d = $cursor->format('Y-m-d');
                $week[] = ['date' => $d, 'inMonth' => $cursor->format('n') === $anchor->format('n'), 'tasks' => $byDay[$d] ?? []];
                $cursor = $cursor->modify('+1 day');
            }
            $weeks[] = $week;
        }

        return $this->view('app/calendar-month', [
            'title'     => 'মাস ভিউ',
            'monthName' => bn_date($firstDay->format('Y-m-d')),
            'weeks'     => $weeks,
            'anchorDate' => $firstDay->format('Y-m-d'),
            'prevDate'  => $firstDay->modify('-1 month')->format('Y-m-d'),
            'nextDate'  => $firstDay->modify('+1 month')->format('Y-m-d'),
        ]);
    }

    private function safeDate(string $date): string
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt !== false ? $dt->format('Y-m-d') : DateBD::today();
    }
}
