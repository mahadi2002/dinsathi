<?php
/** @var string $date @var array|null $review @var array $recent @var bool $isToday */
$this->layout('layouts/app', ['title' => 'Daily Review']);
$moodLabel = (array) config('planner.mood_label');
$moodEmoji = (array) config('planner.mood_emoji');
?>
<div class="page-head">
  <h1>Daily Review</h1>
  <p class="muted"><?= e(bn_date($date)) ?><?= $isToday ? '' : ' (আজ নয়)' ?></p>
</div>

<div class="card narrow-md">
  <form method="post" action="/app/review/<?= e($date) ?>" data-guard>
    <?= csrf_field() ?>
    <div class="field">
      <label><?= $isToday ? 'আজকের দিনটা কেমন গেল?' : 'দিনটা কেমন গিয়েছিল?' ?></label>
      <div class="cluster">
        <?php foreach ($moodLabel as $val => $label): ?>
          <label class="radio">
            <input type="radio" name="mood" value="<?= e($val) ?>" <?= ($review['mood'] ?? '') === $val ? 'checked' : '' ?>>
            <?= e($moodEmoji[$val]) ?> <?= e($label) ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="field">
      <label for="note">সংক্ষিপ্ত নোট</label>
      <textarea id="note" name="note" placeholder="<?= $isToday ? 'আজ কী কী ভালো হলো, কী শেখা হলো...' : 'সেদিন কী কী ভালো হয়েছিল, কী শেখা হয়েছিল...' ?>"><?= e((string) ($review['note'] ?? '')) ?></textarea>
    </div>
    <button class="btn btn--primary" type="submit">সংরক্ষণ করুন</button>
  </form>
</div>

<?php if ($recent !== []): ?>
<section class="section--tight">
  <h2>আগের Review</h2>
  <div class="stack--sm">
    <?php foreach ($recent as $r): ?>
      <?php if ($r['review_date'] === $date) { continue; } ?>
      <a class="card card--link" href="/app/review/<?= e((string) $r['review_date']) ?>">
        <div class="between mb-xs">
          <strong><?= e(bn_date((string) $r['review_date'])) ?></strong>
          <?php if ($r['mood']): ?><span><?= e($moodEmoji[$r['mood']] ?? '') ?></span><?php endif; ?>
        </div>
        <p class="small muted mb-0"><?= e(str_excerpt((string) ($r['note'] ?? ''), 100)) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
