<?php
/** @var array $user @var array|null $planner @var array|null $sms @var bool $smsActive @var string $vapidPublic */
$statusLabel = ['active' => 'সক্রিয়', 'unsubscribed' => 'বন্ধ', 'suspended' => 'স্থগিত', 'expired' => 'মেয়াদোত্তীর্ণ'];
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
      <label class="check">
        <input type="checkbox" name="sms_reminders_on" value="1" <?= (int) $user['sms_reminders_on'] === 1 ? 'checked' : '' ?> <?= !$smsActive ? 'disabled' : '' ?>>
        SMS Reminder চালু রাখুন
        <?php if (!$smsActive): ?><span class="small muted">(SMS Reminder Add-on সক্রিয় করুন)</span><?php endif; ?>
      </label>
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
    <h2 class="card__title">দিনসাথী Planner Subscription</h2>
    <?php if ($planner !== null): ?>
      <p>অবস্থা: <span class="chip chip--<?= $planner['status'] === 'active' ? 'success' : 'urgent' ?>">
        <?= e($statusLabel[$planner['status']] ?? $planner['status']) ?>
      </span></p>
      <p class="small muted">Daily ৳<?= e(number_format((float) $planner['daily_amount'], 2)) ?> (Incl. VAT, SD &amp; SC)</p>
    <?php else: ?>
      <p class="muted">কোনো Subscription নেই।</p>
    <?php endif; ?>
    <a class="btn btn--primary btn--sm" href="/subscribe?manage=1">Subscription পরিচালনা করুন</a>
  </div>

  <div class="card">
    <h2 class="card__title">SMS Reminder Add-on</h2>
    <?php if ($smsActive): ?>
      <p>অবস্থা: <span class="chip chip--success">সক্রিয়</span></p>
      <p class="small muted">Daily ৳<?= e(number_format((float) $sms['daily_amount'], 2)) ?> (Incl. VAT, SD &amp; SC)</p>
      <a class="btn btn--sm" href="/subscribe/sms">Add-on পরিচালনা করুন</a>
    <?php else: ?>
      <p class="muted">Push Notification-এর পাশাপাশি SMS-এও Reminder পেতে Add-on চালু করুন।</p>
      <a class="btn btn--accent btn--sm" href="/subscribe/sms">SMS Reminder Add-on চালু করুন — Daily ৳<?= e($smsAmount) ?></a>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2 class="card__title">Data Export</h2>
    <p class="small muted">আপনার সব Task CSV ফাইলে Export করুন।</p>
    <form method="post" action="/app/settings/export" data-guard>
      <?= csrf_field() ?>
      <button class="btn btn--sm" type="submit">CSV Download করুন</button>
    </form>
  </div>

  <div class="card">
    <h2 class="card__title">Account</h2>
    <p class="small muted">Mobile Number: <?= e((string) $user['mobile_number']) ?></p>
    <form method="post" action="/unsubscribe" data-guard data-confirm="আপনি কি নিশ্চিত Unsubscribe করতে চান?">
      <?= csrf_field() ?>
      <button class="btn btn--ghost btn--sm" type="submit">Unsubscribe করুন</button>
    </form>
  </div>
</div>
