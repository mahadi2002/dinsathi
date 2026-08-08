<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Gateways\GatewayFactory;
use App\Repositories\PushSubscriptionRepo;
use App\Repositories\ReminderRepo;
use App\Repositories\SmsLogRepo;
use App\Repositories\TaskRepo;
use App\Repositories\UserRepo;
use App\Support\DateBD;

/**
 * Two independent delivery channels driven by one `reminders` row — a
 * reminder is "dispatched" per channel, tracked separately, so a failed
 * SMS never blocks push and vice versa.
 */
final class ReminderService
{
    /** Insert (or replace) the pending reminder for a task's due time, subscribed users only. */
    public function scheduleForTask(array $task): void
    {
        $repo = new ReminderRepo();
        $repo->cancelPendingFor('task', (int) $task['id']);

        if ($task['due_at'] === null || $task['reminder_offset_min'] === null) {
            return;
        }
        if (!SubscriptionService::hasAccess((int) $task['user_id'])) {
            return;
        }

        $fireAt = Db::value(
            'SELECT DATE_SUB(?, INTERVAL ? MINUTE)',
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
     * then fans out to push + SMS independently.
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

        // Push is bundled into the planner plan; SMS is the separate paid
        // add-on (it costs real money per message) — both the user's own
        // toggle AND an active sms_reminders subscription are required.
        if ((int) $user['sms_reminders_on'] === 1 && SubscriptionService::hasSmsAccess((int) $user['id'])) {
            $this->dispatchSms((int) $reminder['id'], (string) $user['mobile_number'], $title, $body);
        }
    }

    private function dispatchPush(int $reminderId, int $userId, string $title, string $body): void
    {
        $pushRepo = new PushSubscriptionRepo();
        $subs     = $pushRepo->forUser($userId);

        if ($subs === []) {
            (new ReminderRepo())->setPushStatus($reminderId, 'failed');
            return;
        }

        $gateway  = GatewayFactory::push();
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

    private function dispatchSms(int $reminderId, string $mobile, string $title, string $body): void
    {
        $message = "DinSathi: {$title} — {$body}. অ্যাপ খুলুন: dinsathi.app/app";
        if (mb_strlen($message, 'UTF-8') > 160) {
            $message = mb_substr($message, 0, 157, 'UTF-8') . '...';
        }

        $result = GatewayFactory::sms()->send($mobile, $message);
        $status = $result['success'] ? 'sent' : 'failed';

        (new SmsLogRepo())->create($reminderId, $mobile, $message, config('sms.driver') === 'provider' ? 'provider' : 'mock', $status, $result['response'] ?? []);
        (new ReminderRepo())->setSmsStatus($reminderId, $result['success'] ? 'sent' : 'retrying', 1);
    }

    /** Retries reminders stuck in sms_status='retrying', capped at 3 attempts with 5-minute backoff (handled by cron cadence). */
    public function retrySmsFailures(): int
    {
        $reminderRepo = new ReminderRepo();
        $userRepo     = new UserRepo();
        $taskRepo     = new TaskRepo();
        $retried      = 0;

        foreach ($reminderRepo->retryableSms(3) as $reminder) {
            $user = $userRepo->find((int) $reminder['user_id']);
            if ($user === null || (int) $user['sms_reminders_on'] !== 1) {
                continue;
            }
            if (!SubscriptionService::hasSmsAccess((int) $user['id'])) {
                continue;
            }

            $title = 'রিমাইন্ডার';
            if ($reminder['source_type'] === 'task') {
                $task = $taskRepo->find((int) $reminder['source_id'], (int) $user['id']);
                $title = $task['title'] ?? $title;
            }

            $this->dispatchSms((int) $reminder['id'], (string) $user['mobile_number'], $title, 'রিমাইন্ডার পুনরায় পাঠানো হচ্ছে');
            $retried++;
        }

        return $retried;
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
