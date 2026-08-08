<?php
/** @var int $formStartedAt */
$this->layout('layouts/public', ['title' => 'Contact Us']);
?>
<div class="section wrap prose-sm">
  <h1>Contact Us</h1>
  <p class="lede">প্রশ্ন, সমস্যা বা মতামত — যা কিছুই হোক, লিখে জানান।</p>

  <form method="post" action="/contact" data-guard>
    <?= csrf_field() ?>
    <input type="hidden" name="started_at" value="<?= e((string) $formStartedAt) ?>">
    <div class="visually-offscreen" aria-hidden="true">
      <label for="website">Website</label>
      <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
    </div>

    <div class="field <?= error_for('name') ? 'has-error' : '' ?>">
      <label for="name">নাম</label>
      <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" required>
      <?php if (error_for('name')): ?><span class="error"><?= e(error_for('name')) ?></span><?php endif; ?>
    </div>

    <div class="field <?= error_for('contact') ? 'has-error' : '' ?>">
      <label for="contact">Mobile Number / Email</label>
      <input type="text" id="contact" name="contact" value="<?= e(old('contact')) ?>" required>
      <?php if (error_for('contact')): ?><span class="error"><?= e(error_for('contact')) ?></span><?php endif; ?>
    </div>

    <div class="field <?= error_for('message') ? 'has-error' : '' ?>">
      <label for="message">বার্তা</label>
      <textarea id="message" name="message" required><?= e(old('message')) ?></textarea>
      <?php if (error_for('message')): ?><span class="error"><?= e(error_for('message')) ?></span><?php endif; ?>
    </div>

    <button class="btn btn--primary btn--lg btn--full" type="submit">পাঠিয়ে দিন</button>
  </form>
</div>
