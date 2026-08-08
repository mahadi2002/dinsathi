<?php
/** @var array $sessions @var array $tasks */
use App\Support\DateBD;

$this->layout('layouts/app', ['title' => 'Focus Timer']);
?>
<div class="page-head"><h1>Focus Timer</h1><p class="muted">Pomodoro-স্টাইল — একটানা মনোযোগ দিয়ে কাজ করুন।</p></div>

<div class="card center narrow-sm" data-focus-dial>
  <div class="focus-dial"><span class="focus-dial__time" data-focus-time>25:00</span></div>

  <form method="post" action="/app/focus" data-focus-form class="mt-md">
    <?= csrf_field() ?>
    <input type="hidden" name="duration_sec" data-focus-duration-field value="0">
    <div class="field narrow-xs">
      <label for="task_id">কোন Task-এ Focus করছেন?</label>
      <select id="task_id" name="task_id">
        <option value="">নির্দিষ্ট নয়</option>
        <?php foreach ($tasks as $t): ?>
          <?php $due = $t['due_at'] !== null ? DateBD::toDhaka((string) $t['due_at']) : null; ?>
          <option value="<?= e((string) $t['id']) ?>"><?= e((string) $t['title']) ?><?= $due !== null ? ' (' . e($due->format('d M')) . ')' : '' ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field narrow-xs">
      <label for="minutes">কত মিনিট?</label>
      <input type="number" id="minutes" data-focus-minutes value="25" min="1" max="180">
    </div>
    <button class="btn btn--primary btn--lg" type="button" data-focus-start>Start করুন</button>
    <button class="btn btn--danger btn--lg" type="button" data-focus-stop hidden>থামান ও সংরক্ষণ করুন</button>
  </form>
</div>

<section class="section--tight">
  <h2>সাম্প্রতিক Session</h2>
  <?php if ($sessions === []): ?>
    <p class="muted">এখনো কোনো Session লগ হয়নি।</p>
  <?php else: ?>
    <div class="card">
      <?php foreach ($sessions as $s): ?>
        <div class="task-row">
          <div class="task-row__label">
            <strong><?= e((string) ($s['task_title'] ?? 'নির্দিষ্ট নয়')) ?></strong>
            <span class="small muted"><?= e(bn_date_utc((string) $s['started_at'], true)) ?></span>
          </div>
          <span class="chip"><?= e(bn_num((int) round($s['duration_sec'] / 60))) ?> মিনিট</span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
