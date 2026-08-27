<?php $this->layout('layouts/auth', ['title' => 'Password ভুলে গেছেন?']); ?>
<h1>Password ভুলে গেছেন?</h1>
<p class="lede">আপনার Email দিন — Password Reset করার Link পাঠানো হবে।</p>

<form method="post" action="/forgot-password" data-guard>
  <?= csrf_field() ?>
  <div class="field <?= error_for('email') ? 'has-error' : '' ?>">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" autocomplete="email"
           value="<?= e(old('email')) ?>" autofocus required>
    <?php if (error_for('email')): ?><span class="error"><?= e(error_for('email')) ?></span><?php endif; ?>
  </div>
  <button class="btn btn--primary btn--lg btn--full" type="submit">Reset Link পাঠান</button>
</form>

<p class="center small muted mt-lg"><a href="/login">Login-এ ফিরে যান</a></p>
