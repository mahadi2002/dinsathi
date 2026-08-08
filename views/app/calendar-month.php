<?php
/** @var string $monthName @var array $weeks @var string $anchorDate @var string $prevDate @var string $nextDate */
$this->layout('layouts/app', ['title' => 'মাস ভিউ']);
$dayLabels = ['সোম', 'মঙ্গল', 'বুধ', 'বৃহঃ', 'শুক্র', 'শনি', 'রবি'];
?>
<div class="page-head between">
  <h1 class="mb-0">মাস ভিউ</h1>
  <div class="cluster">
    <a class="small" href="/app/day/<?= e($anchorDate) ?>">দিন ভিউ</a>
    <a class="small" href="/app/week/<?= e($anchorDate) ?>">সপ্তাহ ভিউ</a>
  </div>
</div>

<div class="cal-nav">
  <a class="btn btn--ghost btn--sm" href="/app/month/<?= e($prevDate) ?>">← আগের মাস</a>
  <strong><?= e($monthName) ?></strong>
  <a class="btn btn--ghost btn--sm" href="/app/month/<?= e($nextDate) ?>">পরের মাস →</a>
</div>

<div class="cal-month mb-xs">
  <?php foreach ($dayLabels as $label): ?><div class="small muted center"><?= e($label) ?></div><?php endforeach; ?>
</div>
<?php foreach ($weeks as $week): ?>
  <div class="cal-month">
    <?php foreach ($week as $cell): ?>
      <a href="/app/day/<?= e($cell['date']) ?>" class="cal-month__cell <?= $cell['inMonth'] ? '' : 'is-outside' ?>">
        <span class="cal-month__date"><?= e(bn_num((int) substr($cell['date'], 8, 2))) ?></span>
        <?php foreach (array_slice($cell['tasks'], 0, 3) as $t): ?>
          <div><span class="cal-dot <?= e(list_color_class((string) $t['list_color'])) ?>"></span></div>
        <?php endforeach; ?>
        <?php if (count($cell['tasks']) > 3): ?><span class="small muted">+<?= e(bn_num(count($cell['tasks']) - 3)) ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
