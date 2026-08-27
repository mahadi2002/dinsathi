<?php $this->layout('layouts/auth', ['title' => 'Login']); ?>
<h1>Login করুন</h1>
<p class="lede">আপনার Email ও Password দিয়ে Login করুন।</p>

<form method="post" action="/login" data-guard>
  <?= csrf_field() ?>
  <div class="field <?= error_for('email') ? 'has-error' : '' ?>">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" autocomplete="email"
           value="<?= e(old('email')) ?>" autofocus required>
    <?php if (error_for('email')): ?><span class="error"><?= e(error_for('email')) ?></span><?php endif; ?>
  </div>
  <div class="field <?= error_for('password') ? 'has-error' : '' ?>">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" autocomplete="current-password" required>
    <?php if (error_for('password')): ?><span class="error"><?= e(error_for('password')) ?></span><?php endif; ?>
  </div>
  <button class="btn btn--primary btn--lg btn--full" type="submit">Login করুন</button>
</form>

<p class="center small muted mt-lg"><a href="/forgot-password">Password ভুলে গেছেন?</a></p>
<p class="center small muted">নতুন এখানে? <a href="/register">Register করুন</a></p>
