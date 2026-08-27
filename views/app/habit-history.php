<?php
/** @var array $habit @var array{weeks: list<list<array|null>>, monthLabels: array<int,int>} $grid @var string $range @var int $streak */
$this->layout('layouts/app', ['title' => (string) $habit['name']]);

$monthAbbr = [
    1 => 'জানু', 2 => 'ফেব্রু', 3 => 'মার্চ', 4 => 'এপ্রিল', 5 => 'মে', 6 => 'জুন',
    7 => 'জুলাই', 8 => 'আগস্ট', 9 => 'সেপ্টে', 10 => 'অক্টো', 11 => 'নভে', 12 => 'ডিসে',
];

// Boolean habits only ever hit level 0 (missed) or level 4 (done) — quantity
// habits get the full 1..3 gradient from partial progress toward the target.
$levelOf = static function (array $day): int {
    $i = (float) $day['intensity'];
    if ($i <= 0) { return 0; }
    if ($i < 0.34) { return 1; }
    if ($i < 0.67) { return 2; }
    if ($i < 1)    { return 3; }
    return 4;
};

$titleOf = static function (array $day) use ($habit): string {
    $when = bn_date($day['date']);
    if ($habit['target_quantity'] !== null) {
        return $when . ' — ' . bn_num($day['quantity']) . '/' . bn_num((int) $habit['target_quantity']) . ' ' . (string) ($habit['unit'] ?: '');
    }
    if (!$day['active']) {
        return $when . ' — নির্ধারিত নয়';
    }
    return $when . ($day['completed'] ? ' — সম্পন্ন' : ' — বাদ পড়েছে');
};
?>
<div class="page-head">
  <a href="/app/habits" class="small">← Habit Tracker</a>
  <h1><?= e((string) ($habit['icon'] ?: '🔥')) ?> <?= e((string) $habit['name']) ?></h1>
  <p class="muted">বর্তমান Streak: <strong><?= e(bn_num($streak)) ?> দিন</strong></p>
</div>

<div class="card">
  <div class="between mb-sm">
    <h2 class="card__title mb-0">Contribution Heatmap</h2>
    <div class="cluster">
      <a class="btn btn--ghost btn--sm <?= $range === 'month' ? 'btn--active' : '' ?>" href="?range=month">মাস</a>
      <a class="btn btn--ghost btn--sm <?= $range === 'year' ? 'btn--active' : '' ?>" href="?range=year">বছর</a>
    </div>
  </div>

  <?php if ($grid['weeks'] === []): ?>
    <p class="muted mb-0">এখনো কোনো ইতিহাস নেই।</p>
  <?php else: ?>
    <div class="heatmap-scroll">
      <div class="heatmap" style="grid-template-columns: repeat(<?= count($grid['weeks']) ?>, 1fr);">
        <?php foreach ($grid['weeks'] as $wi => $week): ?>
          <div class="heatmap__col">
            <span class="heatmap__month-label"><?= isset($grid['monthLabels'][$wi]) ? e($monthAbbr[$grid['monthLabels'][$wi]]) : '' ?></span>
            <?php foreach ($week as $day): ?>
              <?php if ($day === null): ?>
                <span class="heatmap__cell heatmap__cell--pad"></span>
              <?php elseif (!$day['active']): ?>
                <span class="heatmap__cell heatmap__cell--inactive" title="<?= e($titleOf($day)) ?>"></span>
              <?php else: ?>
                <span class="heatmap__cell heatmap__cell--l<?= $levelOf($day) ?>" title="<?= e($titleOf($day)) ?>"></span>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="cluster small muted mt-sm">
      <span>কম</span>
      <span class="heatmap__cell heatmap__cell--l0"></span>
      <span class="heatmap__cell heatmap__cell--l1"></span>
      <span class="heatmap__cell heatmap__cell--l2"></span>
      <span class="heatmap__cell heatmap__cell--l3"></span>
      <span class="heatmap__cell heatmap__cell--l4"></span>
      <span>বেশি</span>
    </div>
  <?php endif; ?>
</div>
