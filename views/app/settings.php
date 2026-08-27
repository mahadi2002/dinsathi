<?php
/** @var array $user @var string $vapidPublic */
$this->layout('layouts/app', ['title' => 'Settings']);
?>
<div class="page-head"><h1>Settings</h1></div>

<div class="grid grid--2">
  <div class="card">
    <h2 class="card__title">Notification Preferences</h2>
    <form method="post" action="/app/settings" data-guard>
      <?= csrf_field() ?>
      <div class="grid grid--2">
        <div class="field">
          <label for="push_quiet_start">Quiet Hours শুরু</label>
          <input type="time" id="push_quiet_start" name="push_quiet_start" value="<?= e(substr((string) $user['push_quiet_start'], 0, 5)) ?>">
        </div>
        <div class="field">
          <label for="push_quiet_end">Quiet Hours শেষ</label>
          <input type="time" id="push_quiet_end" name="push_quiet_end" value="<?= e(substr((string) $user['push_quiet_end'], 0, 5)) ?>">
        </div>
      </div>
      <p class="mt-md"><button class="btn btn--primary" type="submit">সংরক্ষণ করুন</button></p>
    </form>

    <hr>
    <p class="small muted">সময়মতো Reminder পেতে Notification Allow করুন।</p>
    <button class="btn btn--accent" type="button" data-push-subscribe hidden>Push Notification চালু করুন</button>
    <?php if ($vapidPublic === ''): ?>
      <p class="small muted">(এই Server-এ Push এখনো Configure করা হয়নি — In-app Notification তবুও কাজ করবে।)</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2 class="card__title">Data Export</h2>
    <p class="small muted">আপনার সব Task, Habit History এবং Focus Session CSV ফাইলে Export করুন।</p>
    <form method="post" action="/app/settings/export" data-guard>
      <?= csrf_field() ?>
      <button class="btn btn--sm" type="submit">CSV Download করুন</button>
    </form>
  </div>

  <div class="card">
    <h2 class="card__title">Account</h2>
    <p class="small muted">Email: <?= e((string) $user['email']) ?></p>
    <form method="post" action="/logout" data-guard>
      <?= csrf_field() ?>
      <button class="btn btn--ghost btn--sm" type="submit">Logout করুন</button>
    </form>
  </div>
</div>
