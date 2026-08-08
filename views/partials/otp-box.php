<?php
/** Exact copy locked in the build spec — do not paraphrase. */
?>
<div class="otp-box" id="otp-box">
  <h2>আপনার Robi বা Airtel Number দিন</h2>
  <p class="muted">Instant Access পাবেন DinSathi-এর সব Feature-এ!</p>

  <form method="post" action="/register/otp" data-guard>
    <?= csrf_field() ?>
    <div class="field text-step-0 text-left narrow-xs">
      <label for="landing-mobile">Mobile Number</label>
      <input type="tel" id="landing-mobile" name="mobile_number" inputmode="numeric" maxlength="11"
             placeholder="01XXXXXXXXX" value="<?= e(old('mobile_number')) ?>" required>
      <span class="hint"><?= e($operatorNote) ?></span>
      <?php if (error_for('mobile_number')): ?><span class="error"><?= e(error_for('mobile_number')) ?></span><?php endif; ?>
    </div>

    <p class="cluster center justify-center">
      <span class="bolt">⚡</span>
      <span class="small">Daily ৳<?= e($dailyAmount) ?> (Incl. VAT, SD &amp; SC) — যেকোনো সময় Unsubscribe করুন</span>
    </p>

    <button class="btn btn--accent btn--lg btn--full" type="submit">OTP পাঠান →</button>
  </form>
</div>
