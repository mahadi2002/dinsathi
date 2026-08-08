<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\PushSubscriptionRepo;
use App\Repositories\TaskListRepo;
use App\Repositories\TaskRepo;
use App\Repositories\UserRepo;
use App\Services\SubscriptionService;

final class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = (int) $this->currentUserId();
        $subs   = SubscriptionService::currentBoth($userId);

        return $this->view('app/settings', [
            'title'       => 'Settings',
            'user'        => (new UserRepo())->find($userId),
            'planner'     => $subs['planner'],
            'sms'         => $subs['sms_reminders'],
            'smsActive'   => SubscriptionService::hasSmsAccess($userId),
            'vapidPublic' => (string) config('push.vapid_public'),
        ]);
    }

    public function update(Request $request): Response
    {
        $userId = (int) $this->currentUserId();
        (new UserRepo())->updateNotificationPrefs(
            $userId,
            $this->validTime($request->str('push_quiet_start'), '22:00'),
            $this->validTime($request->str('push_quiet_end'), '07:00'),
            $request->bool('sms_reminders_on')
        );
        Session::notify('success', 'Settings সংরক্ষণ হয়েছে।');
        return $this->redirect('/app/settings');
    }

    /**
     * A cell starting with =, +, -, or @ is executed as a formula by Excel/Sheets
     * when the CSV is opened — a task title a user typed is free text, not a
     * formula, so a leading tab neutralizes it without changing what's displayed.
     */
    private function csvSafe(string $value): string
    {
        return $value !== '' && str_contains("=+-@", $value[0]) ? "\t" . $value : $value;
    }

    /** A time <input> can be cleared to '' in the browser — falls back rather than writing an invalid TIME value. */
    private function validTime(string $value, string $default): string
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1 ? $value . ':00' : $default . ':00';
    }

    public function pushSubscribe(Request $request): Response
    {
        $userId = (int) $this->currentUserId();
        $data   = $request->json();

        $endpoint = (string) ($data['endpoint'] ?? '');
        $keys     = (array) ($data['keys'] ?? []);
        $p256dh   = (string) ($keys['p256dh'] ?? '');
        $auth     = (string) ($keys['auth'] ?? '');

        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            return $this->json(['ok' => false], 422);
        }

        (new PushSubscriptionRepo())->upsert($userId, $endpoint, $p256dh, $auth);
        return $this->json(['ok' => true]);
    }

    public function pushUnsubscribe(Request $request): Response
    {
        $data = $request->json();
        $endpoint = (string) ($data['endpoint'] ?? '');
        if ($endpoint !== '') {
            (new PushSubscriptionRepo())->deleteByEndpoint($endpoint);
        }
        return $this->json(['ok' => true]);
    }

    public function export(Request $request): Response
    {
        $userId = (int) $this->currentUserId();
        $tasks  = (new TaskRepo())->forList($userId, null);

        $csv = fopen('php://temp', 'w+');
        fputcsv($csv, ['Title', 'List', 'Priority', 'Due (Asia/Dhaka)', 'Completed', 'Notes']);

        foreach ($tasks as $t) {
            $due = \App\Support\DateBD::toDhaka((string) $t['due_at']);
            fputcsv($csv, [
                $this->csvSafe((string) $t['title']),
                $this->csvSafe((string) $t['list_name']),
                $t['priority'],
                $due?->format('Y-m-d H:i') ?? '',
                $t['completed_at'] !== null ? 'Yes' : 'No',
                $this->csvSafe((string) $t['notes']),
            ]);
        }

        rewind($csv);
        $body = (string) stream_get_contents($csv);
        fclose($csv);

        return \App\Core\Response::text($body, 200, 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="dinsathi-tasks.csv"');
    }
}
