<?php
/**
 * @var int $weekTasksTotal @var int $weekTasksDone @var int|null $weekCompletionRate @var int $weekCompletedByDoing
 * @var int $monthTasksTotal @var int $monthTasksDone
 * @var int $weekFocusMinutes @var int $weekFocusCount @var int $monthFocusMinutes
 * @var array $habits @var int $bestStreak @var array $recentReviews
 * @var string $weekLabel @var string $monthLabel @var string $today
 */
$this->layout('layouts/app', ['title' => 'Insights']);
$moodEmoji = (array) config('planner.mood_emoji');

$focusHours = static fn(int $min): string => $min >= 60
    ? bn_num(intdiv($min, 60)) . ' ঘণ্টা ' . ($min % 60 > 0 ? bn_num($min % 60) . ' মিনিট' : '')
    : bn_num($min) . ' মিনিট';
?>
<div class="page-head">
  <h1>Insights</h1>
  <p class="muted">আপনার Progress-এর সংক্ষিপ্ত চিত্র — <?= e($weekLabel) ?></p>
</div>

<div class="grid grid--4 mb-lg">
  <div class="stat-tile">
    <div class="stat-tile__n"><?= e(bn_num($weekTasksDone)) ?><span class="small muted">/<?= e(bn_num($weekTasksTotal)) ?></span></div>
    <div class="stat-tile__l">এই সপ্তাহের Task<?php if ($weekCompletionRate !== null): ?> · <?= e(bn_num($weekCompletionRate)) ?>%<?php endif; ?></div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__n"><?= e(bn_num($monthTasksDone)) ?><span class="small muted">/<?= e(bn_num($monthTasksTotal)) ?></span></div>
    <div class="stat-tile__l">এই মাসের Task · <?= e($monthLabel) ?> থেকে</div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__n"><?= e($focusHours($weekFocusMinutes)) ?></div>
    <div class="stat-tile__l">এই সপ্তাহে Focus · <?= e(bn_num($weekFocusCount)) ?>টি Session</div>
  </div>
  <div class="stat-tile">
    <div class="stat-tile__n">🔥 <?= e(bn_num($bestStreak)) ?></div>
    <div class="stat-tile__l">সেরা Habit Streak · এই মাসে Focus <?= e($focusHours($monthFocusMinutes)) ?></div>
  </div>
</div>

<div class="grid grid--2">
  <section class="card">
    <h2 class="card__title">Habit Streaks</h2>
    <?php if ($habits === []): ?>
      <p class="muted mb-0">এখনো কোনো Habit নেই। <a href="/app/habits">একটি যোগ করুন</a>।</p>
    <?php else: ?>
      <?php foreach ($habits as $habit): ?>
        <div class="task-row">
          <div class="task-row__label">
            <strong><?= e((string) ($habit['icon'] ?: '🔥')) ?> <?= e((string) $habit['name']) ?></strong>
            <div class="flame-row">
              <?php foreach ($habit['recent_days'] as $day): ?>
                <span class="flame-dot <?= !$day['active'] ? 'flame-dot--inactive' : ($day['completed'] ? 'flame-dot--done' : 'flame-dot--missed') ?>"
                      title="<?= e(bn_date($day['date'])) ?>"></span>
              <?php endforeach; ?>
            </div>
          </div>
          <span class="streak-badge">🔥 <?= e(bn_num($habit['streak'])) ?></span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2 class="card__title">সাম্প্রতিক Daily Review</h2>
    <?php if ($recentReviews === []): ?>
      <p class="muted mb-0">এখনো কোনো Review লেখা হয়নি। <a href="/app/review/<?= e($today) ?>">আজকের Review লিখুন</a>।</p>
    <?php else: ?>
      <div class="stack--sm">
        <?php foreach ($recentReviews as $r): ?>
          <a class="card card--link" href="/app/review/<?= e((string) $r['review_date']) ?>">
            <div class="between mb-xs">
              <strong><?= e(bn_date((string) $r['review_date'])) ?></strong>
              <?php if ($r['mood']): ?><span><?= e($moodEmoji[$r['mood']] ?? '') ?></span><?php endif; ?>
            </div>
            <?php if (!empty($r['note'])): ?><p class="small muted mb-0"><?= e(str_excerpt((string) $r['note'], 80)) ?></p><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php if ($weekCompletedByDoing > 0 || $weekTasksDone > 0): ?>
<p class="small muted center mt-lg mb-0">এই সপ্তাহে মোট <?= e(bn_num($weekCompletedByDoing)) ?>টি Task সম্পন্ন হয়েছে — চালিয়ে যান! 🎉</p>
<?php endif; ?>
