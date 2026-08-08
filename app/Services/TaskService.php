<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\TaskRepo;
use App\Support\RRuleLite;

/** Controllers stay thin — task creation/update policy (caps, recurrence, reminders) lives here. */
final class TaskService
{
    public function __construct(
        private TaskRepo $tasks = new TaskRepo(),
        private ReminderService $reminders = new ReminderService(),
        private RecurrenceService $recurrence = new RecurrenceService(),
    ) {
    }

    /**
     * @return array{ok: bool, error?: string, id?: int}
     */
    public function create(int $userId, array $input): array
    {
        // Every route that reaches here already sits behind the 'sub' (planner)
        // middleware — there is no free path — but this stays a real query, not
        // an assumption, since a service method shouldn't trust its caller alone.
        $subscribed = SubscriptionService::hasAccess($userId);
        if (!$subscribed) {
            return ['ok' => false, 'error' => 'Planner ব্যবহার করতে Subscribe করুন।'];
        }

        $recurrenceRule = $input['recurrence_rule'] ?? null;
        if ($recurrenceRule !== null && !RRuleLite::isValid($recurrenceRule)) {
            return ['ok' => false, 'error' => 'Recurrence-এর ধরন সঠিক নয়।'];
        }
        // A template with no due date has no anchor to generate instances from —
        // RecurrenceService::generateFor() would silently create zero instances,
        // leaving an invisible task the user can never see or delete again.
        if ($recurrenceRule !== null && ($input['due_at'] ?? null) === null) {
            return ['ok' => false, 'error' => 'Recurring Task তৈরি করতে Due Date দিতে হবে।'];
        }

        $isTemplate = $recurrenceRule !== null;

        $id = $this->tasks->create([
            'user_id'             => $userId,
            'list_id'             => $input['list_id'],
            'title'               => $input['title'],
            'notes'               => $input['notes'] ?? null,
            'priority'            => $this->validPriority($input['priority'] ?? null),
            'due_at'              => $input['due_at'] ?? null,
            'is_template'         => $isTemplate ? 1 : 0,
            'recurrence_rule'     => $recurrenceRule,
            'reminder_offset_min' => $input['reminder_offset_min'] ?? null,
        ]);

        if ($isTemplate) {
            $template = $this->tasks->find($id, $userId);
            if ($template !== null) {
                $this->recurrence->generateFor($template);
            }
        } else {
            $task = $this->tasks->find($id, $userId);
            if ($task !== null) {
                $this->reminders->scheduleForTask($task);
            }
        }

        return ['ok' => true, 'id' => $id];
    }

    public function update(int $id, int $userId, array $input): bool
    {
        $existing = $this->tasks->find($id, $userId);
        if ($existing === null) {
            return false;
        }

        $this->tasks->update($id, $userId, [
            'title'               => $input['title'],
            'notes'               => $input['notes'] ?? null,
            'priority'            => $this->validPriority($input['priority'] ?? null),
            'due_at'              => $input['due_at'] ?? null,
            'list_id'             => $input['list_id'],
            'recurrence_rule'     => (int) $existing['is_template'] === 1 ? $existing['recurrence_rule'] : null,
            'reminder_offset_min' => $input['reminder_offset_min'] ?? null,
        ]);

        $updated = $this->tasks->find($id, $userId);
        if ($updated !== null && (int) $updated['is_template'] === 0) {
            $this->reminders->scheduleForTask($updated);
        }

        return true;
    }

    public function delete(int $id, int $userId): void
    {
        $this->reminders->cancelForTask($id);
        $this->tasks->delete($id, $userId);
    }

    /** Guards the DB's ENUM column against an empty string — '' passes `??` unnoticed but isn't a valid enum member. */
    private function validPriority(?string $priority): string
    {
        return in_array($priority, ['low', 'medium', 'high', 'urgent'], true) ? $priority : 'medium';
    }

    public function toggleComplete(int $id, int $userId): bool
    {
        $task = $this->tasks->find($id, $userId);
        if ($task === null) {
            return false;
        }
        $this->tasks->setCompleted($id, $userId, $task['completed_at'] === null);
        return true;
    }
}
