<?php
/** @var string $date @var array $tasks @var array $lists @var string $prevDate @var string $nextDate */
$this->layout('layouts/app', ['title' => 'দিন ভিউ']);
$priorityLabel = (array) config('planner.priority_label');
?>
<div class="page-head between">
  <h1 class="mb-0">দিন ভিউ</h1>
  <div class="cluster">
    <a class="small" href="/app/week/<?= e($date) ?>">সপ্তাহ ভিউ</a>
    <a class="small" href="/app/month/<?= e($date) ?>">মাস ভিউ</a>
  </div>
</div>

<div class="cal-nav">
  <a class="btn btn--ghost btn--sm" href="/app/day/<?= e($prevDate) ?>">← আগের দিন</a>
  <strong><?= e(bn_date($date)) ?></strong>
  <a class="btn btn--ghost btn--sm" href="/app/day/<?= e($nextDate) ?>">পরের দিন →</a>
</div>

<div class="card">
  <?php if ($tasks === []): ?>
    <p class="muted mb-0">এই দিনে কোনো Task নেই।</p>
  <?php else: ?>
    <?php foreach ($tasks as $task): ?>
      <div class="task-row">
        <button class="task-check <?= $task['completed_at'] !== null ? 'task-check--done' : '' ?>" type="button"
                data-toggle-url="/app/tasks/<?= e((string) $task['id']) ?>/complete" aria-label="সম্পন্ন করুন">
          <?php if ($task['completed_at'] !== null): ?>✓<?php endif; ?>
        </button>
        <a href="/app/tasks/<?= e((string) $task['id']) ?>" class="task-row__label text-inherit">
          <strong class="<?= $task['completed_at'] !== null ? 'task-title--done' : '' ?>"><?= e((string) $task['title']) ?></strong>
          <span class="small muted"><?= e((string) $task['list_name']) ?><?php if ($task['due_at']): ?> · <?= e(\App\Support\DateBD::toDhaka((string) $task['due_at'])?->format('h:i A')) ?><?php endif; ?></span>
        </a>
        <span class="chip chip--<?= e((string) $task['priority']) ?>"><?= e($priorityLabel[$task['priority']] ?? '') ?></span>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
