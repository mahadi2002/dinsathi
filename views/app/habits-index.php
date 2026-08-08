<?php
/** @var array $habits */
$this->layout('layouts/app', ['title' => 'Habit Tracker']);
?>
<div class="page-head"><h1>Habit Tracker</h1><p class="muted">প্রতিদিনের অভ্যাস — শান্তভাবে, চাপহীনভাবে ধরে রাখুন।</p></div>

<div class="card mb-lg">
  <h2 class="card__title">নতুন Habit</h2>
  <form method="post" action="/app/habits" data-guard class="cluster">
    <?= csrf_field() ?>
    <input type="text" name="name" placeholder="যেমন: সকালে হাঁটা" required class="field-grow">
    <input type="text" name="icon" placeholder="Emoji (ঐচ্ছিক)" maxlength="20" class="field-narrow-sm">
    <button class="btn btn--primary" type="submit">যোগ করুন</button>
  </form>
  <p class="small muted mt-sm">সপ্তাহের সব দিনের জন্য তৈরি হবে — পরে চাইলে পরিবর্তন করা যাবে।</p>
</div>

<?php if ($habits === []): ?>
  <div class="empty-state">
    <h3>এখনো কোনো Habit নেই</h3>
    <p class="muted mb-0">উপরে থেকে প্রথম Habit যোগ করুন।</p>
  </div>
<?php else: ?>
  <div class="grid grid--2">
    <?php foreach ($habits as $habit): ?>
      <div class="card">
        <div class="between">
          <h3 class="card__title mb-0"><?= e((string) ($habit['icon'] ?: '🔥')) ?> <?= e((string) $habit['name']) ?></h3>
          <span class="streak-badge"><?= e(bn_num($habit['streak'])) ?> দিন</span>
        </div>
        <div class="flame-row flame-row--spaced">
          <?php foreach ($habit['recent_days'] as $day): ?>
            <span class="flame-dot <?= !$day['active'] ? 'flame-dot--inactive' : ($day['completed'] ? 'flame-dot--done' : 'flame-dot--missed') ?>"
                  title="<?= e(bn_date($day['date'])) ?>"></span>
          <?php endforeach; ?>
        </div>
        <div class="cluster">
          <form method="post" action="/app/habits/<?= e((string) $habit['id']) ?>/checkin" data-guard>
            <?= csrf_field() ?>
            <button class="btn btn--sm <?= $habit['done_today'] ? 'btn--accent' : 'btn--primary' ?>" type="submit">
              <?= $habit['done_today'] ? 'আজ সম্পন্ন ✓' : 'আজ Check-in করুন' ?>
            </button>
          </form>
          <a class="btn btn--ghost btn--sm" href="/app/habits/<?= e((string) $habit['id']) ?>/history">History</a>
          <form method="post" action="/app/habits/<?= e((string) $habit['id']) ?>" data-guard data-confirm="Habit সরিয়ে ফেলবেন?">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="DELETE">
            <button class="btn btn--ghost btn--sm" type="submit">সরান</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
