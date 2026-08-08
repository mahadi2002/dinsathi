<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\HabitRepo;
use App\Services\HabitService;

final class HabitController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = (int) $this->currentUserId();
        $service = new HabitService();

        $habits = array_map(static function (array $h) use ($service) {
            return $h + [
                'streak'      => $service->streak((int) $h['id'], (string) $h['active_days']),
                'done_today'  => $service->isDoneToday((int) $h['id']),
                'recent_days' => $service->recentDays((int) $h['id'], (string) $h['active_days'], 7),
            ];
        }, (new HabitRepo())->forUser($userId));

        return $this->view('app/habits-index', ['title' => 'Habit Tracker', 'habits' => $habits]);
    }

    public function store(Request $request): Response
    {
        $userId = (int) $this->currentUserId();
        $v = Validator::make($request->body(), ['name' => 'required|max:100'], ['name' => 'নাম']);

        if ($v->fails()) {
            $v->flash();
            return $this->redirect('/app/habits');
        }

        $days = $request->arr('active_days');
        $activeDays = $days === [] ? 'MO,TU,WE,TH,FR,SA,SU' : implode(',', $days);

        (new HabitRepo())->create($userId, $v->get('name'), $request->str('icon') ?: null, $activeDays);
        Session::notify('success', 'নতুন Habit যোগ হয়েছে।');
        return $this->redirect('/app/habits');
    }

    public function checkin(Request $request, string $id): Response
    {
        $userId = (int) $this->currentUserId();
        $habit  = (new HabitRepo())->find((int) $id, $userId);
        if ($habit === null) {
            $this->notFound();
        }

        $nowDone = (new HabitService())->checkin((int) $id);

        if ($request->wantsJson()) {
            return $this->json(['ok' => true, 'done' => $nowDone]);
        }
        return $this->back($request, '/app/habits');
    }

    public function history(Request $request, string $id): Response
    {
        $userId = (int) $this->currentUserId();
        $habit  = (new HabitRepo())->find((int) $id, $userId);
        if ($habit === null) {
            $this->notFound();
        }

        $service = new HabitService();

        return $this->view('app/habit-history', [
            'title'  => (string) $habit['name'],
            'habit'  => $habit,
            'days'   => $service->recentDays((int) $id, (string) $habit['active_days'], 84),
            'streak' => $service->streak((int) $id, (string) $habit['active_days']),
        ]);
    }

    public function destroy(Request $request, string $id): Response
    {
        $userId = (int) $this->currentUserId();
        (new HabitRepo())->archive((int) $id, $userId);
        Session::notify('success', 'Habit সরানো হয়েছে।');
        return $this->redirect('/app/habits');
    }
}
