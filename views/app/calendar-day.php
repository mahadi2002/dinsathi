<?php
/** @var string $date @var array $tasks @var array $lists @var string $prevDate @var string $nextDate */
$this->layout('layouts/app', ['title' => 'দিন ভিউ']);
$priorityLabel = (array) config('planner.priority_label');

// Grouped into hourly slots so a task can be dragged onto a different hour —
// every task shown here already has a due_at (CalendarController::day()
// only fetches tasks with due_at in range), so 'H' is always defined.
$byHour = array_fill(0, 24, []);
foreach ($tasks as $task) {
    $dhaka = \App\Support\DateBD::toDhaka((string) $task['due_at']);
    if ($dhaka !== null) {
        $byHour[(int) $dhaka->format('G')][] = $task;
    }
}
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

<?php if ($tasks === []): ?>
  <div class="card"><p class="muted mb-0">এই দিনে কোনো Task নেই।</p></div>
<?php else: ?>
  <p class="small muted mb-sm">অন্য সময়ে সরাতে একটি Task ধরে টেনে নতুন Hour-এ ছাড়ুন।</p>
  <div class="cal-day" data-reschedule-date="<?= e($date) ?>">
    <?php for ($h = 0; $h < 24; $h++): ?>
      <div class="cal-day__slot" data-hour-slot="<?= e((string) $h) ?>">
        <div class="cal-day__hour"><?= e(bn_num(sprintf('%02d:00', $h))) ?></div>
        <div class="cal-day__tasks">
          <?php foreach ($byHour[$h] as $task): ?>
            <div class="task-row task-row--draggable" data-task-card
                 data-task-id="<?= e((string) $task['id']) ?>"
                 data-due-time="<?= e(\App\Support\DateBD::toDhaka((string) $task['due_at'])?->format('H:i') ?? '00:00') ?>">
              <button class="task-check <?= $task['completed_at'] !== null ? 'task-check--done' : '' ?>" type="button"
                      data-toggle-url="/app/tasks/<?= e((string) $task['id']) ?>/complete" aria-label="সম্পন্ন করুন">
                <?php if ($task['completed_at'] !== null): ?>✓<?php endif; ?>
              </button>
              <a href="/app/tasks/<?= e((string) $task['id']) ?>" class="task-row__label text-inherit">
                <strong data-toggle-title class="<?= $task['completed_at'] !== null ? 'task-title--done' : '' ?>"><?= e((string) $task['title']) ?></strong>
                <span class="small muted"><?= e((string) $task['list_name']) ?></span>
              </a>
              <span class="chip chip--<?= e((string) $task['priority']) ?>"><?= e($priorityLabel[$task['priority']] ?? '') ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endfor; ?>
  </div>
<?php endif; ?>
