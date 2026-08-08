<?php
/** @var string $mobile @var array|null $planner @var array|null $sms @var bool $otpSent */
$this->layout('layouts/auth', ['title' => 'Subscribe']);
?>
<div class="page-head center">
  <h1>Subscribe করুন</h1>
  <p class="lede center">Unlimited Task, Recurring Task, Push Reminder, Habit Tracker, Focus Timer, Daily Review, Insights — সব একসাথে।</p>
</div>

<div class="paywall">
  <p class="eyebrow">দিনসাথী Planner</p>
  <p class="price">৳<?= e($dailyAmount) ?><span class="small muted">/day</span></p>
  <p class="muted">Incl. VAT, SD &amp; SC &nbsp;|&nbsp; যেকোনো সময় Unsubscribe করুন</p>

  <?php if ($planner !== null && in_array($planner['status'], ['suspended', 'expired'], true)): ?>
    <div class="notice notice--warn text-left narrow-sm">
      <span>বর্তমান Subscription অবস্থা: <strong><?= e($planner['status'] === 'suspended' ? 'স্থগিত (Low Balance)' : 'মেয়াদোত্তীর্ণ') ?></strong> — আবার Subscribe করুন।</span>
    </div>
  <?php endif; ?>

  <?php if ($planner !== null && $planner['status'] === 'active'): ?>
    <div class="notice notice--info text-left narrow-sm">
      <span>আপনার Planner Subscription সক্রিয় আছে।</span>
    </div>
  <?php elseif (!$otpSent): ?>
    <form method="post" action="/subscribe/otp" data-guard class="narrow-sm">
      <?= csrf_field() ?>
      <p class="small muted"><?= e($mobile) ?> নম্বরে OTP পাঠানো হবে</p>
      <button class="btn btn--accent btn--lg btn--full" type="submit">OTP পাঠান →</button>
    </form>
  <?php else: ?>
    <form method="post" action="/subscribe/confirm" data-guard class="narrow-sm">
      <?= csrf_field() ?>
      <input type="hidden" name="otp" id="otp-combined">
      <div class="otp-input">
        <?php for ($i = 0; $i < 6; $i++): ?>
          <input type="text" inputmode="numeric" maxlength="1" aria-label="সংখ্যা <?= e(bn_num($i + 1)) ?>">
        <?php endfor; ?>
      </div>
      <button class="btn btn--accent btn--lg btn--full" type="submit">Confirm Subscription</button>
    </form>
    <form method="post" action="/subscribe/otp" data-guard class="mt-md">
      <?= csrf_field() ?>
      <button class="btn btn--ghost btn--sm" type="submit">OTP আবার পাঠান</button>
    </form>
  <?php endif; ?>
</div>

<?php if ($planner !== null && $planner['status'] === 'active'): ?>
  <div class="section--tight center">
    <p class="small muted">SMS-এও Reminder পেতে চান? <a href="/subscribe/sms">SMS Reminder Add-on</a> চালু করুন।</p>
    <a class="btn btn--primary btn--sm" href="/app">App-এ যান</a>
  </div>
<?php endif; ?>

<?php if ($planner !== null): ?>
  <div class="section--tight center">
    <form method="post" action="/unsubscribe" data-guard data-confirm="আপনি কি নিশ্চিত Unsubscribe করতে চান?">
      <?= csrf_field() ?>
      <button class="btn btn--ghost btn--sm" type="submit">Unsubscribe করুন</button>
    </form>
  </div>
<?php endif; ?>
