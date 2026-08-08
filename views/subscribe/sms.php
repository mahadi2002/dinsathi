<?php
/** @var string $mobile @var array|null $sms @var bool $otpSent */
$this->layout('layouts/auth', ['title' => 'SMS Reminder']);
?>
<div class="page-head center">
  <h1>SMS Reminder Add-on</h1>
  <p class="lede center">Push Notification মিস হয়ে গেলেও Task Reminder সরাসরি আপনার মোবাইলে SMS আকারে পৌঁছাবে।</p>
</div>

<div class="paywall">
  <p class="eyebrow">SMS Reminder</p>
  <p class="price">৳<?= e($smsAmount) ?><span class="small muted">/day</span></p>
  <p class="muted">Incl. VAT, SD &amp; SC &nbsp;|&nbsp; যেকোনো সময় বন্ধ করুন</p>

  <?php if ($sms !== null && in_array($sms['status'], ['suspended', 'expired'], true)): ?>
    <div class="notice notice--warn text-left narrow-sm">
      <span>বর্তমান Add-on অবস্থা: <strong><?= e($sms['status'] === 'suspended' ? 'স্থগিত (Low Balance)' : 'মেয়াদোত্তীর্ণ') ?></strong> — আবার চালু করুন।</span>
    </div>
  <?php endif; ?>

  <?php if ($sms !== null && $sms['status'] === 'active'): ?>
    <div class="notice notice--info text-left narrow-sm">
      <span>SMS Reminder Add-on সক্রিয় আছে।</span>
    </div>
  <?php elseif (!$otpSent): ?>
    <form method="post" action="/subscribe/sms/otp" data-guard class="narrow-sm">
      <?= csrf_field() ?>
      <p class="small muted"><?= e($mobile) ?> নম্বরে OTP পাঠানো হবে</p>
      <button class="btn btn--accent btn--lg btn--full" type="submit">OTP পাঠান →</button>
    </form>
  <?php else: ?>
    <form method="post" action="/subscribe/sms/confirm" data-guard class="narrow-sm">
      <?= csrf_field() ?>
      <input type="hidden" name="otp" id="otp-combined">
      <div class="otp-input">
        <?php for ($i = 0; $i < 6; $i++): ?>
          <input type="text" inputmode="numeric" maxlength="1" aria-label="সংখ্যা <?= e(bn_num($i + 1)) ?>">
        <?php endfor; ?>
      </div>
      <button class="btn btn--accent btn--lg btn--full" type="submit">Confirm করুন</button>
    </form>
    <form method="post" action="/subscribe/sms/otp" data-guard class="mt-md">
      <?= csrf_field() ?>
      <button class="btn btn--ghost btn--sm" type="submit">OTP আবার পাঠান</button>
    </form>
  <?php endif; ?>
</div>

<div class="section--tight center">
  <?php if ($sms !== null && $sms['status'] === 'active'): ?>
    <form method="post" action="/unsubscribe/sms" data-guard data-confirm="আপনি কি নিশ্চিত SMS Reminder বন্ধ করতে চান?">
      <?= csrf_field() ?>
      <button class="btn btn--ghost btn--sm" type="submit">Add-on বন্ধ করুন</button>
    </form>
  <?php endif; ?>
  <p class="mt-md"><a class="small" href="/app/settings">Settings-এ ফিরে যান</a></p>
</div>
