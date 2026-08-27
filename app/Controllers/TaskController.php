<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\SubtaskRepo;
use App\Repositories\TagRepo;
use App\Repositories\TaskListRepo;
use App\Repositories\TaskRepo;
use App\Services\TaskService;
use App\Support\DateBD;

final class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = (int) $this->currentUserId();
        $listId = $request->int('list_id') ?: null;
        $tagId  = $request->int('tag_id') ?: null;
        $q      = $request->str('q');

        return $this->view('app/tasks-index', [
            'title'      => 'সব Task',
            'tasks'      => (new TaskRepo())->search($userId, $listId, $tagId, $q),
            'lists'      => (new TaskListRepo())->forUser($userId),
            'tags'       => (new TagRepo())->forUser($userId),
            'activeList' => $listId,
            'activeTag'  => $tagId,
            'q'          => $q,
        ]);
    }

    public function store(Request $request): Response
    {
        $userId = (int) $this->currentUserId();

        $rules = [
            'title'    => 'required|max:255',
            'list_id'  => 'required|int',
            'priority' => 'in:low,medium,high,urgent',
        ];
        $v = Validator::make($request->body(), $rules, ['title' => 'শিরোনাম', 'list_id' => 'List']);

        if ($v->fails()) {
            $v->flash();
            return $this->back($request, '/app/tasks');
        }

        $list = (new TaskListRepo())->find((int) $v->get('list_id'), $userId);
        if ($list === null) {
            Session::notify('error', 'নির্বাচিত List পাওয়া যায়নি। আবার চেষ্টা করুন।');
            return $this->back($request, '/app/tasks');
        }

        $dueAt = DateBD::combineToUtc($request->str('due_date'), $request->str('due_time') ?: null);

        $result = (new TaskService())->create($userId, [
            'list_id'             => (int) $v->get('list_id'),
            'title'               => $v->get('title'),
            'notes'               => $request->str('notes') ?: null,
            'priority'            => $v->get('priority', 'medium'),
            'due_at'              => $dueAt,
            'recurrence_rule'     => $request->str('recurrence_rule') ?: null,
            'reminder_offset_min' => $request->str('reminder_offset_min') !== '' ? $request->int('reminder_offset_min') : null,
        ]);

        if (!$result['ok']) {
            Session::notify('error', $result['error']);
            return $this->back($request, '/app/tasks');
        }

        if ($request->str('tags_csv') !== '') {
            $this->syncTags($userId, (int) $result['id'], $request->str('tags_csv'));
        }

        Session::notify('success', 'Task তৈরি হয়েছে।');
        return $this->back($request, '/app/tasks');
    }

    public function show(Request $request, string $id): Response
    {
        $userId = (int) $this->currentUserId();
        $task   = (new TaskRepo())->find((int) $id, $userId);
        if ($task === null) {
            $this->notFound();
        }

        return $this->view('app/task-show', [
            'title'      => (string) $task['title'],
            'task'       => $task,
            'lists'      => (new TaskListRepo())->forUser($userId),
            'subtasks'   => (new SubtaskRepo())->forTask((int) $task['id']),
            'tags'       => (new TagRepo())->forTask((int) $task['id']),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $userId = (int) $this->currentUserId();

        $v = Validator::make($request->body(), [
            'title'    => 'required|max:255',
            'list_id'  => 'required|int',
            'priority' => 'in:low,medium,high,urgent',
        ], ['title' => 'শিরোনাম']);

        if ($v->fails()) {
            $v->flash();
            return $this->back($request, '/app/tasks/' . $id);
        }

        // A task may only move into a list the same user owns — otherwise a tampered
        // list_id would let a task's list_id FK point at another user's task_lists row,
        // which that other user deleting their own list would then cascade-delete.
        if ((new TaskListRepo())->find((int) $v->get('list_id'), $userId) === null) {
            Session::notify('error', 'নির্বাচিত List পাওয়া যায়নি। আবার চেষ্টা করুন।');
            return $this->back($request, '/app/tasks/' . $id);
        }

        $dueAt = DateBD::combineToUtc($request->str('due_date'), $request->str('due_time') ?: null);
        $applyScope = $request->str('apply_scope') === 'future' ? 'future' : 'this_only';

        $ok = (new TaskService())->update((int) $id, $userId, [
            'title'               => $v->get('title'),
            'notes'               => $request->str('notes') ?: null,
            'priority'            => $v->get('priority', 'medium'),
            'due_at'              => $dueAt,
            'list_id'             => (int) $v->get('list_id'),
            'reminder_offset_min' => $request->str('reminder_offset_min') !== '' ? $request->int('reminder_offset_min') : null,
        ], $applyScope);

        if (!$ok) {
            $this->notFound();
        }

        $this->syncTags($userId, (int) $id, $request->str('tags_csv'));

        Session::notify('success', 'Task Update হয়েছে।');
        return $this->redirect('/app/tasks/' . $id);
    }

    /**
     * Drag-to-reschedule from the calendar day/week views — a lightweight
     * sibling to update() that only ever touches due_at, called via
     * fetch() PATCH with a JSON body {due_date, due_time}, both Asia/Dhaka
     * local. Never touches a template row (TaskService::reschedule()
     * refuses is_template = 1).
     */
    public function reschedule(Request $request, string $id): Response
    {
        $userId = (int) $this->currentUserId();

        $data = $request->json();
        $dueDate = isset($data['due_date']) && is_string($data['due_date']) ? trim($data['due_date']) : '';
        $dueTime = isset($data['due_time']) && is_string($data['due_time']) ? trim($data['due_time']) : null;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            return $this->json(['ok' => false, 'error' => 'তারিখ সঠিক নয়।'], 422);
        }
        if ($dueTime !== null && $dueTime !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $dueTime)) {
            return $this->json(['ok' => false, 'error' => 'সময় সঠিক নয়।'], 422);
        }

        $dueAtUtc = DateBD::combineToUtc($dueDate, $dueTime);
        $ok = (new TaskService())->reschedule((int) $id, $userId, $dueAtUtc);

        if (!$ok) {
            return $this->json(['ok' => false], 404);
        }

        return $this->json(['ok' => true, 'due_at' => $dueAtUtc]);
    }

    public function destroy(Request $request, string $id): Response
    {
        $userId = (int) $this->currentUserId();
        (new TaskService())->delete((int) $id, $userId);
        Session::notify('success', 'Task মুছে ফেলা হয়েছে।');
        return $this->back($request, '/app/tasks');
    }

    public function complete(Request $request, string $id): Response
    {
        $userId = (int) $this->currentUserId();
        $ok = (new TaskService())->toggleComplete((int) $id, $userId);
        if (!$ok) {
            $this->notFound();
        }

        if ($request->wantsJson()) {
            return $this->json(['ok' => true]);
        }
        return $this->back($request, '/app');
    }

    public function addSubtask(Request $request, string $id): Response
    {
        $userId = (int) $this->currentUserId();
        $task   = (new TaskRepo())->find((int) $id, $userId);
        if ($task === null) {
            $this->notFound();
        }

        $title = $request->str('title');
        if ($title !== '') {
            (new SubtaskRepo())->create((int) $id, $title);
        }

        return $this->redirect('/app/tasks/' . $id);
    }

    private function syncTags(int $userId, int $taskId, string $csv): void
    {
        $names = array_filter(array_map('trim', explode(',', $csv)));
        if ($names === []) {
            (new TagRepo())->syncForTask($taskId, []);
            return;
        }
        $tagRepo = new TagRepo();
        $ids = array_map(static fn($name) => $tagRepo->findOrCreate($userId, $name), $names);
        $tagRepo->syncForTask($taskId, $ids);
    }

    public function toggleSubtask(Request $request, string $id): Response
    {
        $userId = (int) $this->currentUserId();
        $ok = (new SubtaskRepo())->toggle((int) $id, $userId);
        if (!$ok) {
            $this->notFound();
        }

        if ($request->wantsJson()) {
            return $this->json(['ok' => true]);
        }
        return $this->back($request, '/app/tasks');
    }
}
