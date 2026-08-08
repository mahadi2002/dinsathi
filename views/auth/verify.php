<?php
/** @var string $mobile @var string $action */
$this->layout('layouts/auth', ['title' => 'OTP যাচাই করুন']);
?>
<h1>OTP যাচাই করুন</h1>
<p class="lede"><?= e($mobile) ?> নম্বরে পাঠানো ৬ সংখ্যার Code বসান।</p>

<form method="post" action="<?= e($action) ?>" data-guard>
  <?= csrf_field() ?>
  <input type="hidden" name="otp" id="otp-combined">
  <div class="otp-input">
    <?php for ($i = 0; $i < 6; $i++): ?>
      <input type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="সংখ্যা <?= e(bn_num($i + 1)) ?>">
    <?php endfor; ?>
  </div>
  <button class="btn btn--primary btn--lg btn--full" type="submit">যাচাই করুন</button>
</form>

<form method="post" action="/otp/resend" data-guard class="mt-md">
  <?= csrf_field() ?>
  <button class="btn btn--ghost btn--full" type="submit" data-resend-otp data-cooldown="60">আবার পাঠান</button>
</form>
