<?php $this->layout('layouts/auth', ['title' => 'Register']); ?>
<h1>নতুন Account তৈরি করুন</h1>
<p class="lede">আপনার Robi বা Airtel নম্বর দিয়ে শুরু করুন — কোনো Password লাগবে না।</p>

<form method="post" action="/register/otp" data-guard>
  <?= csrf_field() ?>
  <div class="field <?= error_for('mobile_number') ? 'has-error' : '' ?>">
    <label for="mobile_number">Mobile Number</label>
    <input type="tel" id="mobile_number" name="mobile_number" inputmode="numeric" maxlength="11"
           placeholder="01XXXXXXXXX" value="<?= e(old('mobile_number')) ?>" autofocus required>
    <span class="hint"><?= e($operatorNote) ?></span>
    <?php if (error_for('mobile_number')): ?><span class="error"><?= e(error_for('mobile_number')) ?></span><?php endif; ?>
  </div>
  <button class="btn btn--primary btn--lg btn--full" type="submit">OTP পাঠান →</button>
</form>

<p class="center small muted mt-lg">আগে থেকেই Account আছে? <a href="/login">Login করুন</a></p>
