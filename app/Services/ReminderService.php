<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Gateways\MockPushGateway;
use App\Repositories\PushSubscriptionRepo;
use App\Repositories\ReminderRepo;
use App\Repositories\TaskRepo;
use App\Repositories\UserRepo;
use App\Support\DateBD;

/**
 * Push-only reminder dispatch. SMS delivery (and the paid sms_reminders
 * add-on it used to require) has been removed entirely — every reminder
 * fans out through MockPushGateway only. The real Web Push crypto
 * implementation is a later phase (see WebPushGateway); MockPushGateway
 * (writes an in-app `notifications` row) is what runs today.
 */
final class ReminderService
{
    /** Insert (or replace) the pending reminder for a task's due time. */
    public function scheduleForTask(array $task): void
    {
        $repo = new ReminderRepo();
        $repo->cancelPendingFor('task', (int) $task['id']);

        if ($task['due_at'] === null || $task['reminder_offset_min'] === null) {
            return;
        }

        $fireAt = Db::value(
            'SELECT CAST(? AS DATETIME) - INTERVAL ? MINUTE',
            [$task['due_at'], (int) $task['reminder_offset_min']]
        );

        $repo->create((int) $task['user_id'], 'task', (int) $task['id'], (string) $fireAt);
    }

    public function cancelForTask(int $taskId): void
    {
        (new ReminderRepo())->cancelPendingFor('task', $taskId);
    }

    /**
     * Run by cron/run_jobs.php every minute. Claims each due reminder with a
     * row lock (idempotency — two overlapping cron runs can't double-send),
     * then dispatches push.
     */
    public function dispatchDue(): array
    {
        $reminderRepo = new ReminderRepo();
        $userRepo     = new UserRepo();
        $taskRepo     = new TaskRepo();
        $stats        = ['dispatched' => 0, 'quiet_skipped' => 0, 'requeued' => 0];

        foreach ($reminderRepo->duePending(Db::nowUtc()) as $reminder) {
            $user = $userRepo->find((int) $reminder['user_id']);
            if ($user === null) {
                continue;
            }

            $nowDhaka = DateBD::nowDhaka()->format('H:i:s');
            $isUrgent = $reminder['source_type'] === 'task'
                && ($taskRepo->find((int) $reminder['source_id'], (int) $user['id'])['priority'] ?? '') === 'urgent';

            if (!$isUrgent && DateBD::isWithinQuietHours($nowDhaka, (string) $user['push_quiet_start'], (string) $user['push_quiet_end'])) {
                if (!$reminderRepo->claim((int) $reminder['id'])) {
                    continue;
                }
                $windowEnd = $this->nextQuietWindowEnd((string) $user['push_quiet_end']);
                $reminderRepo->requeueAfterQuietHours((int) $reminder['id'], $windowEnd);
                $stats['requeued']++;
                continue;
            }

            if (!$reminderRepo->claim((int) $reminder['id'])) {
                continue;
            }

            $this->dispatchOne($reminder, $user, $taskRepo);
            $stats['dispatched']++;
        }

        return $stats;
    }

    private function dispatchOne(array $reminder, array $user, TaskRepo $taskRepo): void
    {
        $title = 'রিমাইন্ডার';
        $body  = 'আপনার একটি কাজ আছে।';

        if ($reminder['source_type'] === 'task') {
            $task = $taskRepo->find((int) $reminder['source_id'], (int) $user['id']);
            if ($task !== null) {
                $title = (string) $task['title'];
                $due   = DateBD::toDhaka((string) $task['due_at']);
                $body  = $due !== null ? 'সময়: ' . $due->format('h:i A') : 'আজকের কাজ';
            }
        }

        $this->dispatchPush((int) $reminder['id'], (int) $user['id'], $title, $body);
    }

    private function dispatchPush(int $reminderId, int $userId, string $title, string $body): void
    {
        $pushRepo = new PushSubscriptionRepo();
        $subs     = $pushRepo->forUser($userId);

        if ($subs === []) {
            (new ReminderRepo())->setPushStatus($reminderId, 'failed');
            return;
        }

        $gateway  = new MockPushGateway();
        $anySent  = false;

        foreach ($subs as $sub) {
            $result = $gateway->send($userId, $sub, "দিনসাথী: {$title}", $body, '/app');
            if ($result['expired']) {
                $pushRepo->delete((int) $sub['id']);
                continue;
            }
            $anySent = $anySent || $result['success'];
        }

        (new ReminderRepo())->setPushStatus($reminderId, $anySent ? 'sent' : 'failed');
    }

    private function nextQuietWindowEnd(string $quietEndHis): string
    {
        $dhakaNow = DateBD::nowDhaka();
        $endToday = $dhakaNow->setTime(
            (int) substr($quietEndHis, 0, 2),
            (int) substr($quietEndHis, 3, 2)
        );
        $target = $endToday > $dhakaNow ? $endToday : $endToday->modify('+1 day');

        return $target->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
