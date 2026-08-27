<?php
/** @var array $task @var array $lists @var array $subtasks @var array $tags */
use App\Support\DateBD;

$this->layout('layouts/app', ['title' => (string) $task['title']]);
$priorityLabel = (array) config('planner.priority_label');
$offsets = (array) config('planner.reminder_offsets');
$due = DateBD::toDhaka((string) $task['due_at']);
$doneCount = count(array_filter($subtasks, static fn($s) => $s['completed_at'] !== null));
?>
<div class="page-head">
  <a href="/app/tasks" class="small">← সব Task</a>
</div>

<div class="grid grid--2">
  <div class="card">
    <h2 class="card__title">Task Edit করুন</h2>
    <?php if ((int) $task['is_template'] === 1): ?>
      <p class="small muted">এটি একটি পুনরাবৃত্ত (Recurring) Task-এর Template। Save করার আগে জিজ্ঞাসা করা হবে এটি শুধু Template-এ, নাকি ভবিষ্যতের Task-গুলোতেও প্রয়োগ করবেন।</p>
    <?php endif; ?>
    <form method="post" action="/app/tasks/<?= e((string) $task['id']) ?>" data-guard<?= (int) $task['is_template'] === 1 ? ' data-recurring-form' : '' ?>>
      <?= csrf_field() ?>
      <input type="hidden" name="_method" value="PATCH">
      <?php if ((int) $task['is_template'] === 1): ?>
        <input type="hidden" name="apply_scope" value="this_only" data-apply-scope>
      <?php endif; ?>

      <div class="field <?= error_for('title') ? 'has-error' : '' ?>">
        <label for="title">শিরোনাম</label>
        <input type="text" id="title" name="title" value="<?= e(old('title', (string) $task['title'])) ?>" required>
      </div>
      <div class="field">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes"><?= e((string) ($task['notes'] ?? '')) ?></textarea>
      </div>
      <div class="grid grid--2">
        <div class="field">
          <label for="list_id">List</label>
          <select id="list_id" name="list_id">
            <?php foreach ($lists as $list): ?>
              <option value="<?= e((string) $list['id']) ?>" <?= (int) $list['id'] === (int) $task['list_id'] ? 'selected' : '' ?>><?= e((string) $list['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="priority">Priority</label>
          <select id="priority" name="priority">
            <?php foreach ($priorityLabel as $val => $label): ?>
              <option value="<?= e($val) ?>" <?= $val === $task['priority'] ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="due_date">Due Date</label>
          <input type="date" id="due_date" name="due_date" value="<?= e($due?->format('Y-m-d') ?? '') ?>">
        </div>
        <div class="field">
          <label for="due_time">Due Time</label>
          <input type="time" id="due_time" name="due_time" value="<?= e($due?->format('H:i') ?? '') ?>">
        </div>
        <div class="field col-span-all">
          <label for="tags">Tags (কমা দিয়ে আলাদা করুন)</label>
          <input type="text" id="tags" name="tags_csv" value="<?= e(implode(', ', array_column($tags, 'name'))) ?>">
        </div>
        <div class="field">
          <label for="reminder_offset_min">Reminder</label>
          <select id="reminder_offset_min" name="reminder_offset_min">
            <option value="">Reminder ছাড়া</option>
            <?php foreach ($offsets as $min => $label): ?>
              <option value="<?= e((string) $min) ?>" <?= (string) $task['reminder_offset_min'] === (string) $min ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button class="btn btn--primary" type="submit">Update করুন</button>
    </form>

    <form method="post" action="/app/tasks/<?= e((string) $task['id']) ?>" data-guard class="mt-md"
          data-confirm="আপনি কি নিশ্চিত মুছে ফেলতে চান?">
      <?= csrf_field() ?>
      <input type="hidden" name="_method" value="DELETE">
      <button class="btn btn--danger btn--sm" type="submit">Task মুছে ফেলুন</button>
    </form>
  </div>

  <?php if ((int) $task['is_template'] === 1): ?>
    <div class="recur-prompt" data-recur-prompt hidden>
      <div class="recur-prompt__box">
        <h3 class="card__title">পরিবর্তন কীভাবে প্রয়োগ করবেন?</h3>
        <p class="small muted">এটি একটি Recurring Task-এর Template — Google Calendar-এর মতোই, এই পরিবর্তন শুধু Template-এ রাখবেন নাকি ইতিমধ্যে তৈরি হওয়া ভবিষ্যতের Task-গুলোতেও (অতীতেরগুলো কখনো বদলায় না) প্রয়োগ করবেন?</p>
        <div class="cluster mt-md">
          <button type="button" class="btn btn--ghost btn--sm" data-recur-choice="this_only">শুধু এই Template</button>
          <button type="button" class="btn btn--primary btn--sm" data-recur-choice="future">এই এবং ভবিষ্যতের সব</button>
        </div>
        <button type="button" class="btn btn--ghost btn--sm mt-sm" data-recur-cancel>বাতিল</button>
      </div>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="between">
      <h2 class="card__title mb-0">Subtasks / Checklist</h2>
      <?php if ($subtasks !== []): ?><span class="chip"><?= e(bn_num($doneCount) . '/' . bn_num(count($subtasks))) ?></span><?php endif; ?>
    </div>

    <?php foreach ($subtasks as $sub): ?>
      <div class="task-row">
        <button class="task-check <?= $sub['completed_at'] !== null ? 'task-check--done' : '' ?>" type="button"
                data-toggle-url="/app/subtasks/<?= e((string) $sub['id']) ?>/toggle" aria-label="সম্পন্ন করুন">
          <?php if ($sub['completed_at'] !== null): ?>✓<?php endif; ?>
        </button>
        <span data-toggle-title class="<?= $sub['completed_at'] !== null ? 'task-title--done' : '' ?>"><?= e((string) $sub['title']) ?></span>
      </div>
    <?php endforeach; ?>
    <?php if ($subtasks === []): ?><p class="muted small">এখনো কোনো Subtask নেই।</p><?php endif; ?>

    <form method="post" action="/app/tasks/<?= e((string) $task['id']) ?>/subtasks" data-guard class="cluster mt-md">
      <?= csrf_field() ?>
      <input type="text" name="title" placeholder="নতুন Subtask..." required class="flex-1">
      <button class="btn btn--sm" type="submit">যোগ করুন</button>
    </form>
  </div>
</div>
