<?php $this->layout('layouts/auth', ['title' => 'Login']); ?>
<h1>Login করুন</h1>
<p class="lede">আপনার Registered নম্বরে OTP পাঠানো হবে।</p>

<form method="post" action="/login/otp" data-guard>
  <?= csrf_field() ?>
  <div class="field <?= error_for('mobile_number') ? 'has-error' : '' ?>">
    <label for="mobile_number">Mobile Number</label>
    <input type="tel" id="mobile_number" name="mobile_number" inputmode="numeric" maxlength="11"
           placeholder="01XXXXXXXXX" value="<?= e(old('mobile_number')) ?>" autofocus required>
    <?php if (error_for('mobile_number')): ?><span class="error"><?= e(error_for('mobile_number')) ?></span><?php endif; ?>
  </div>
  <button class="btn btn--primary btn--lg btn--full" type="submit">OTP পাঠান →</button>
</form>

<p class="center small muted mt-lg">নতুন এখানে? <a href="/register">Register করুন</a></p>
