<?php
/**
 * @var array $user @var string $today
 * @var array $todayTasks @var array $overdueTasks @var int $doneCount @var int $totalCount
 * @var array $habits @var array $lists @var array $notifications
 */
use App\Support\DateBD;

$this->layout('layouts/app', ['title' => 'ড্যাশবোর্ড']);

$priorityLabel = (array) config('planner.priority_label');
$pct = $totalCount > 0 ? pct_step((float) $doneCount, (float) $totalCount) : 0;
?>
<div class="page-head">
  <h1>স্বাগতম<?= !empty($user['display_name']) ? ', ' . e($user['display_name']) : '' ?></h1>
  <p class="muted"><?= e(bn_date($today)) ?> — আজকের পরিকল্পনা নিচে দেখুন।</p>
</div>

<div class="grid grid--2">
  <div class="card">
    <div class="between">
      <h2 class="card__title mb-0">আজকের কাজ</h2>
      <?php if ($totalCount > 0): ?>
        <div class="progress-ring progress-<?= e((string) $pct) ?>" role="img"
             aria-label="<?= e(bn_num($doneCount) . '/' . bn_num($totalCount) . ' কাজ সম্পন্ন') ?>">
          <span class="progress-ring__label"><?= e(bn_num($doneCount) . '/' . bn_num($totalCount)) ?></span>
        </div>
      <?php else: ?>
        <span class="chip">আজ কোনো Task নেই</span>
      <?php endif; ?>
    </div>

    <?php if ($todayTasks === [] && $overdueTasks === []): ?>
      <p class="muted mb-0">আজ কোনো কাজ বাকি নেই। 🎉</p>
    <?php else: ?>
      <?php foreach ($overdueTasks as $task): ?>
        <div class="task-row task-row--overdue">
          <button class="task-check" type="button" data-toggle-url="/app/tasks/<?= e((string) $task['id']) ?>/complete" aria-label="সম্পন্ন করুন"></button>
          <div class="task-row__label">
            <strong><?= e((string) $task['title']) ?></strong>
            <span class="small text-danger">মেয়াদোত্তীর্ণ — <?= e(bn_date_utc((string) $task['due_at'])) ?></span>
          </div>
          <span class="chip chip--<?= e((string) $task['priority']) ?>"><?= e($priorityLabel[$task['priority']] ?? '') ?></span>
        </div>
      <?php endforeach; ?>
      <?php foreach ($todayTasks as $task): ?>
        <div class="task-row <?= $task['completed_at'] !== null ? '' : '' ?>">
          <button class="task-check <?= $task['completed_at'] !== null ? 'task-check--done' : '' ?>" type="button"
                  data-toggle-url="/app/tasks/<?= e((string) $task['id']) ?>/complete" aria-label="সম্পন্ন করুন">
            <?php if ($task['completed_at'] !== null): ?>✓<?php endif; ?>
          </button>
          <div class="task-row__label">
            <strong data-toggle-title class="<?= $task['completed_at'] !== null ? 'task-title--done' : '' ?>"><?= e((string) $task['title']) ?></strong>
            <span class="small muted"><?= $task['due_at'] !== null ? e(DateBD::toDhaka((string) $task['due_at'])?->format('h:i A')) : 'সময় নির্ধারিত নয়' ?></span>
          </div>
          <span class="chip chip--<?= e((string) $task['priority']) ?>"><?= e($priorityLabel[$task['priority']] ?? '') ?></span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
    <p class="mb-0 mt-md"><a href="/app/day/<?= e($today) ?>">দিন ভিউ দেখুন →</a></p>
  </div>

  <div class="card">
    <h2 class="card__title">Habit Tracker</h2>
    <?php if ($habits === []): ?>
      <p class="muted mb-0">এখনো কোনো Habit যোগ করেননি।</p>
      <a class="btn btn--sm" href="/app/habits">Habit যোগ করুন</a>
    <?php else: ?>
      <?php foreach (array_slice($habits, 0, 5) as $habit): ?>
        <div class="task-row">
          <div class="task-row__label">
            <strong><?= e((string) $habit['name']) ?></strong>
            <div class="flame-row">
              <?php foreach ($habit['recent_days'] as $day): ?>
                <span class="flame-dot <?= !$day['active'] ? 'flame-dot--inactive' : ($day['completed'] ? 'flame-dot--done' : 'flame-dot--missed') ?>"
                      title="<?= e(bn_date($day['date'])) ?>"></span>
              <?php endforeach; ?>
            </div>
          </div>
          <span class="streak-badge">🔥 <?= e(bn_num($habit['streak'])) ?></span>
          <?php if (($habit['target_quantity'] ?? null) === null): ?>
            <button class="btn btn--sm <?= $habit['done_today'] ? 'btn--accent' : '' ?>" type="button"
                    data-toggle-url="/app/habits/<?= e((string) $habit['id']) ?>/checkin" data-toggle-mode="label"
                    data-toggle-done-class="btn--accent" data-toggle-done-text="✓" data-toggle-undone-text="Check-in">
              <?= $habit['done_today'] ? '✓' : 'Check-in' ?>
            </button>
          <?php else: ?>
            <form method="post" action="/app/habits/<?= e((string) $habit['id']) ?>/checkin" data-guard>
              <?= csrf_field() ?>
              <button class="btn btn--sm <?= $habit['done_today'] ? 'btn--accent' : '' ?>" type="submit">
                <?= $habit['done_today'] ? '✓' : '+১' ?>
              </button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <p class="mb-0 mt-sm"><a href="/app/habits">সব Habit দেখুন →</a></p>
    <?php endif; ?>
  </div>
</div>

<section class="section--tight">
  <div class="between">
    <h2>Lists</h2>
    <a class="small" href="/app/lists">সব দেখুন</a>
  </div>
  <div class="grid grid--4">
    <?php foreach ($lists as $list): ?>
      <a class="card card--link" href="/app/tasks?list_id=<?= e((string) $list['id']) ?>">
        <span class="list-dot <?= e(list_color_class((string) $list['color_hex'])) ?>"></span>
        <strong><?= e((string) $list['name']) ?></strong>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section--tight">
  <a class="card card--link" href="/app/insights">
    <strong>📊 আপনার Insights দেখুন →</strong>
    <p class="small muted mb-0">সাপ্তাহিক Progress, Focus সময়, Habit Streak — সব এক জায়গায়।</p>
  </a>
</section>
