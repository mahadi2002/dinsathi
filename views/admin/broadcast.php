<?php $this->layout('layouts/admin', ['title' => 'Broadcast']); ?>
<h1>Broadcast</h1>
<p class="muted">সব ব্যবহারকারীকে একসাথে একটি In-app + Push Announcement পাঠান।</p>

<div class="card narrow-md">
  <form method="post" action="/admin/broadcast" data-guard>
    <?= csrf_field() ?>
    <div class="field <?= error_for('title') ? 'has-error' : '' ?>">
      <label for="title">শিরোনাম</label>
      <input type="text" id="title" name="title" value="<?= e(old('title')) ?>" required>
      <?php if (error_for('title')): ?><span class="error"><?= e(error_for('title')) ?></span><?php endif; ?>
    </div>
    <div class="field <?= error_for('message') ? 'has-error' : '' ?>">
      <label for="message">বার্তা (সর্বোচ্চ ২০০ অক্ষর)</label>
      <textarea id="message" name="message" maxlength="200" required><?= e(old('message')) ?></textarea>
      <?php if (error_for('message')): ?><span class="error"><?= e(error_for('message')) ?></span><?php endif; ?>
    </div>
    <p class="mt-md"><button class="btn btn--primary" type="submit">পাঠিয়ে দিন</button></p>
  </form>
</div>
