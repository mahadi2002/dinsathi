<?php $this->layout('layouts/auth', ['title' => 'Admin Login']); ?>
<h1>Admin Login</h1>
<p class="lede">Email ও Password দিয়ে Login করুন।</p>

<form method="post" action="/admin/login" data-guard>
  <?= csrf_field() ?>
  <div class="field">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" autofocus required>
  </div>
  <div class="field">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>
  </div>
  <button class="btn btn--primary btn--lg btn--full" type="submit">Login</button>
</form>
