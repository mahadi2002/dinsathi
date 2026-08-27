<?php $this->layout('layouts/auth', ['title' => 'Register']); ?>
<h1>নতুন Account তৈরি করুন</h1>
<p class="lede">Email ও Password দিয়ে শুরু করুন — সম্পূর্ণ Free।</p>

<form method="post" action="/register" data-guard>
  <?= csrf_field() ?>
  <div class="field <?= error_for('email') ? 'has-error' : '' ?>">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" autocomplete="email"
           value="<?= e(old('email')) ?>" autofocus required>
    <?php if (error_for('email')): ?><span class="error"><?= e(error_for('email')) ?></span><?php endif; ?>
  </div>
  <div class="field <?= error_for('password') ? 'has-error' : '' ?>">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
    <span class="hint">কমপক্ষে ৮ অক্ষর</span>
    <?php if (error_for('password')): ?><span class="error"><?= e(error_for('password')) ?></span><?php endif; ?>
  </div>
  <div class="field <?= error_for('password_confirmation') ? 'has-error' : '' ?>">
    <label for="password_confirmation">Password আবার লিখুন</label>
    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" minlength="8" required>
    <?php if (error_for('password_confirmation')): ?><span class="error"><?= e(error_for('password_confirmation')) ?></span><?php endif; ?>
  </div>
  <button class="btn btn--primary btn--lg btn--full" type="submit">Account তৈরি করুন</button>
</form>

<p class="center small muted mt-lg">আগে থেকেই Account আছে? <a href="/login">Login করুন</a></p>
