<?php
/** @var string $monday @var array $days @var string $prevDate @var string $nextDate */
$this->layout('layouts/app', ['title' => 'সপ্তাহ ভিউ']);
$dayLabels = ['সোম', 'মঙ্গল', 'বুধ', 'বৃহঃ', 'শুক্র', 'শনি', 'রবি'];
?>
<div class="page-head between">
  <h1 class="mb-0">সপ্তাহ ভিউ</h1>
  <div class="cluster">
    <a class="small" href="/app/day/<?= e($monday) ?>">দিন ভিউ</a>
    <a class="small" href="/app/month/<?= e($monday) ?>">মাস ভিউ</a>
  </div>
</div>

<div class="cal-nav">
  <a class="btn btn--ghost btn--sm" href="/app/week/<?= e($prevDate) ?>">← আগের সপ্তাহ</a>
  <strong><?= e(bn_date($monday)) ?> থেকে</strong>
  <a class="btn btn--ghost btn--sm" href="/app/week/<?= e($nextDate) ?>">পরের সপ্তাহ →</a>
</div>

<div class="cal-week">
  <?php $i = 0; foreach ($days as $date => $tasks): ?>
    <div class="cal-week__day">
      <h4><a class="text-inherit" href="/app/day/<?= e($date) ?>"><?= e($dayLabels[$i++]) ?> · <?= e(bn_num((int) substr($date, 8, 2))) ?></a></h4>
      <?php foreach ($tasks as $t): ?>
        <a href="/app/tasks/<?= e((string) $t['id']) ?>" class="small cal-week__task">
          <span class="cal-dot <?= e(list_color_class((string) $t['list_color'])) ?>"></span><?= e((string) $t['title']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>
