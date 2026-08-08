<?php
/** @var array $habit @var array $days @var int $streak */
$this->layout('layouts/app', ['title' => (string) $habit['name']]);
?>
<div class="page-head">
  <a href="/app/habits" class="small">← Habit Tracker</a>
  <h1><?= e((string) ($habit['icon'] ?: '🔥')) ?> <?= e((string) $habit['name']) ?></h1>
  <p class="muted">বর্তমান Streak: <strong><?= e(bn_num($streak)) ?> দিন</strong></p>
</div>

<div class="card">
  <h2 class="card__title">গত ১২ সপ্তাহ</h2>
  <div class="habit-history-grid">
    <?php foreach ($days as $day): ?>
      <span class="flame-dot flame-dot--lg <?= !$day['active'] ? 'flame-dot--inactive' : ($day['completed'] ? 'flame-dot--done' : 'flame-dot--missed') ?>"
            title="<?= e(bn_date($day['date'])) ?>"></span>
    <?php endforeach; ?>
  </div>
</div>
