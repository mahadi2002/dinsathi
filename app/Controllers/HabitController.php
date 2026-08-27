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
                'streak'         => $service->streak((int) $h['id'], (string) $h['active_days']),
                'done_today'     => $service->isDoneToday((int) $h['id']),
                'recent_days'    => $service->recentDays((int) $h['id'], (string) $h['active_days'], 7),
                'today_quantity' => $h['target_quantity'] !== null ? $service->quantityToday((int) $h['id']) : null,
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

        // Both null keeps a plain boolean habit — a target with no unit (or vice
        // versa) is still treated as boolean, not a half-configured quantity habit.
        $targetQuantity = $request->str('target_quantity') !== '' ? max(1, $request->int('target_quantity')) : null;
        $unit = $targetQuantity !== null ? ($request->str('unit') ?: null) : null;

        (new HabitRepo())->create($userId, $v->get('name'), $request->str('icon') ?: null, $activeDays, $targetQuantity, $unit);
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

        $service = new HabitService();

        if ($habit['target_quantity'] !== null) {
            $amount = $request->int('amount', 1);
            if ($amount === 0) {
                $amount = 1;
            }
            $result = $service->logQuantity((int) $id, (int) $habit['target_quantity'], $amount);

            if ($request->wantsJson()) {
                return $this->json(['ok' => true, 'done' => $result['done'], 'quantity' => $result['quantity']]);
            }
            return $this->back($request, '/app/habits');
        }

        $nowDone = $service->checkin((int) $id);

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
        $range   = $request->str('range') === 'month' ? 'month' : 'year';
        // Month view: ~5 weeks back. Year view: 53 weeks (371 days), like
        // GitHub's contribution graph. toGrid() pads both out to full
        // Monday-start weeks regardless of the exact count requested here.
        $windowDays = $range === 'month' ? 35 : 371;
        $targetQuantity = $habit['target_quantity'] !== null ? (int) $habit['target_quantity'] : null;

        $days = $service->recentDays((int) $id, (string) $habit['active_days'], $windowDays, $targetQuantity);

        return $this->view('app/habit-history', [
            'title'  => (string) $habit['name'],
            'habit'  => $habit,
            'range'  => $range,
            'grid'   => $service->toGrid($days),
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
