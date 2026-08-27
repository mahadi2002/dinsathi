<?php
/** @var array $tasks @var array $lists @var array $tags @var int|null $activeList @var int|null $activeTag @var string $q */
use App\Support\DateBD;

$this->layout('layouts/app', ['title' => 'সব Task']);
$priorityLabel = (array) config('planner.priority_label');
$offsets = (array) config('planner.reminder_offsets');

/** Rebuilds the filter querystring, swapping one param while keeping the others. */
$filterUrl = static function (array $override) use ($activeList, $activeTag, $q): string {
    $params = array_filter([
        'list_id' => $override['list_id'] ?? $activeList,
        'tag_id'  => $override['tag_id'] ?? $activeTag,
        'q'       => $override['q'] ?? $q,
    ], static fn($v) => $v !== null && $v !== '');
    return '/app/tasks' . ($params === [] ? '' : '?' . http_build_query($params));
};
?>
<div class="page-head between">
  <h1 class="mb-0">সব Task</h1>
</div>

<form method="get" action="/app/tasks" class="cluster mb-md">
  <?php if ($activeList !== null): ?><input type="hidden" name="list_id" value="<?= e((string) $activeList) ?>"><?php endif; ?>
  <?php if ($activeTag !== null): ?><input type="hidden" name="tag_id" value="<?= e((string) $activeTag) ?>"><?php endif; ?>
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Task খুঁজুন..." class="field-grow">
  <button class="btn btn--sm" type="submit">খুঁজুন</button>
  <?php if ($q !== ''): ?><a class="btn btn--ghost btn--sm" href="<?= e($filterUrl(['q' => ''])) ?>">মুছুন</a><?php endif; ?>
</form>

<div class="cluster mb-xs">
  <a class="chip <?= $activeList === null ? 'chip--medium' : '' ?>" href="<?= e($filterUrl(['list_id' => null])) ?>">সব List</a>
  <?php foreach ($lists as $list): ?>
    <a class="chip <?= $activeList === (int) $list['id'] ? 'chip--medium' : '' ?>" href="<?= e($filterUrl(['list_id' => $list['id']])) ?>">
      <span class="list-dot <?= e(list_color_class((string) $list['color_hex'])) ?>"></span> <?= e((string) $list['name']) ?>
    </a>
  <?php endforeach; ?>
</div>

<?php if ($tags !== []): ?>
<div class="cluster mb-lg">
  <span class="small muted">Tag:</span>
  <a class="chip small <?= $activeTag === null ? 'chip--medium' : '' ?>" href="<?= e($filterUrl(['tag_id' => null])) ?>">সব</a>
  <?php foreach ($tags as $tag): ?>
    <a class="chip small <?= $activeTag === (int) $tag['id'] ? 'chip--medium' : '' ?>" href="<?= e($filterUrl(['tag_id' => $tag['id']])) ?>">#<?= e((string) $tag['name']) ?></a>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="mb-lg"></div>
<?php endif; ?>

<div class="card mb-lg" id="quick-add">
  <h2 class="card__title">নতুন Task</h2>
  <form method="post" action="/app/tasks" data-guard>
    <?= csrf_field() ?>
    <div class="grid grid--2">
      <div class="field col-span-all <?= error_for('title') ? 'has-error' : '' ?>">
        <label for="title">শিরোনাম</label>
        <input type="text" id="title" name="title" value="<?= e(old('title')) ?>" required>
        <?php if (error_for('title')): ?><span class="error"><?= e(error_for('title')) ?></span><?php endif; ?>
      </div>
      <div class="field">
        <label for="list_id">List</label>
        <select id="list_id" name="list_id" required>
          <?php foreach ($lists as $list): ?>
            <option value="<?= e((string) $list['id']) ?>" <?= $activeList === (int) $list['id'] ? 'selected' : '' ?>><?= e((string) $list['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="priority">Priority</label>
        <select id="priority" name="priority">
          <?php foreach ($priorityLabel as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= $val === 'medium' ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="due_date">Due Date</label>
        <input type="date" id="due_date" name="due_date">
      </div>
      <div class="field">
        <label for="due_time">Due Time</label>
        <input type="time" id="due_time" name="due_time">
      </div>
      <div class="field">
        <label for="recurrence_rule">Recurring</label>
        <select id="recurrence_rule" name="recurrence_rule">
          <option value="">না</option>
          <option value="DAILY">প্রতিদিন</option>
          <option value="WEEKLY:MO,WE,FR">প্রতি সপ্তাহে (সোম,বুধ,শুক্র)</option>
          <option value="MONTHLY:1">প্রতি মাসের ১ তারিখে</option>
        </select>
      </div>
      <div class="field">
        <label for="reminder_offset_min">Reminder</label>
        <select id="reminder_offset_min" name="reminder_offset_min">
          <option value="">Reminder ছাড়া</option>
          <?php foreach ($offsets as $min => $label): ?>
            <option value="<?= e((string) $min) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <button class="btn btn--primary" type="submit">Task যোগ করুন</button>
  </form>
</div>

<?php if ($tasks === []): ?>
  <div class="empty-state">
    <h3><?= $q !== '' || $activeTag !== null ? 'কোনো Task পাওয়া যায়নি' : 'এখনো কোনো Task নেই' ?></h3>
    <p class="muted mb-0"><?= $q !== '' || $activeTag !== null ? 'অন্য কিছু দিয়ে খুঁজে দেখুন।' : 'উপরের Form দিয়ে প্রথম Task যোগ করুন।' ?></p>
  </div>
<?php else: ?>
  <div class="card">
    <?php foreach ($tasks as $task): ?>
      <div class="task-row">
        <button class="task-check <?= $task['completed_at'] !== null ? 'task-check--done' : '' ?>" type="button"
                data-toggle-url="/app/tasks/<?= e((string) $task['id']) ?>/complete" aria-label="সম্পন্ন করুন">
          <?php if ($task['completed_at'] !== null): ?>✓<?php endif; ?>
        </button>
        <a href="/app/tasks/<?= e((string) $task['id']) ?>" class="task-row__label text-inherit">
          <strong data-toggle-title class="<?= $task['completed_at'] !== null ? 'task-title--done' : '' ?>"><?= e((string) $task['title']) ?></strong>
          <span class="small muted">
            <span class="list-dot <?= e(list_color_class((string) $task['list_color'])) ?>"></span> <?= e((string) $task['list_name']) ?>
            <?php if ($task['due_at'] !== null): ?> · <?= e(bn_date_utc((string) $task['due_at'], true)) ?><?php endif; ?>
            <?php if ($task['parent_template_id'] !== null): ?> · 🔁 Recurring<?php endif; ?>
          </span>
        </a>
        <span class="chip chip--<?= e((string) $task['priority']) ?>"><?= e($priorityLabel[$task['priority']] ?? '') ?></span>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
