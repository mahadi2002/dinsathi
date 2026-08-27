<?php
/** @var string $token */
$this->layout('layouts/auth', ['title' => 'নতুন Password সেট করুন']);
?>
<h1>নতুন Password সেট করুন</h1>
<p class="lede">আপনার Account-এর জন্য একটি নতুন Password দিন।</p>

<form method="post" action="/reset-password/<?= e($token) ?>" data-guard>
  <?= csrf_field() ?>
  <div class="field <?= error_for('password') ? 'has-error' : '' ?>">
    <label for="password">নতুন Password</label>
    <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" autofocus required>
    <span class="hint">কমপক্ষে ৮ অক্ষর</span>
    <?php if (error_for('password')): ?><span class="error"><?= e(error_for('password')) ?></span><?php endif; ?>
  </div>
  <div class="field <?= error_for('password_confirmation') ? 'has-error' : '' ?>">
    <label for="password_confirmation">Password আবার লিখুন</label>
    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" minlength="8" required>
    <?php if (error_for('password_confirmation')): ?><span class="error"><?= e(error_for('password_confirmation')) ?></span><?php endif; ?>
  </div>
  <button class="btn btn--primary btn--lg btn--full" type="submit">Password পরিবর্তন করুন</button>
</form>
