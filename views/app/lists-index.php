<?php
/** @var array $lists */
$this->layout('layouts/app', ['title' => 'আমার Lists']);
$colors = (array) config('planner.list_colors');
?>
<div class="page-head"><h1>আমার Lists</h1></div>

<div class="card mb-lg">
  <h2 class="card__title">নতুন List</h2>
  <form method="post" action="/app/lists" data-guard class="cluster">
    <?= csrf_field() ?>
    <input type="text" name="name" placeholder="List-এর নাম" required class="field-grow">
    <span class="cluster" role="radiogroup" aria-label="রং বেছে নিন">
      <?php foreach ($colors as $i => $c): ?>
        <label class="check small">
          <input type="radio" name="color_hex" value="<?= e($c) ?>" <?= $i === 0 ? 'checked' : '' ?>>
          <span class="list-dot swatch-dot swatch-<?= e((string) ($i + 1)) ?>"></span>
        </label>
      <?php endforeach; ?>
    </span>
    <button class="btn btn--primary" type="submit">তৈরি করুন</button>
  </form>
</div>

<div class="grid grid--3">
  <?php foreach ($lists as $list): ?>
    <div class="card">
      <div class="between mb-xs">
        <span class="list-dot swatch-dot <?= e(list_color_class((string) $list['color_hex'])) ?>"></span>
        <?php if (!$list['is_default']): ?>
          <form method="post" action="/app/lists/<?= e((string) $list['id']) ?>" data-guard
                data-confirm="এই List মুছে ফেললে এর সব Task-ও মুছে যাবে। নিশ্চিত?">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="DELETE">
            <button class="btn btn--ghost btn--sm" type="submit">মুছুন</button>
          </form>
        <?php endif; ?>
      </div>
      <strong><?= e((string) $list['name']) ?></strong>
      <p class="mb-0"><a class="small" href="/app/tasks?list_id=<?= e((string) $list['id']) ?>">Task দেখুন →</a></p>

      <details class="mt-sm">
        <summary class="small">নাম/রং পরিবর্তন করুন</summary>
        <form method="post" action="/app/lists/<?= e((string) $list['id']) ?>" data-guard class="stack--sm mt-sm">
          <?= csrf_field() ?>
          <input type="hidden" name="_method" value="PATCH">
          <input type="text" name="name" value="<?= e((string) $list['name']) ?>" required>
          <span class="cluster" role="radiogroup" aria-label="রং বেছে নিন">
            <?php foreach ($colors as $i => $c): ?>
              <label class="check small">
                <input type="radio" name="color_hex" value="<?= e($c) ?>" <?= $c === $list['color_hex'] ? 'checked' : '' ?>>
                <span class="list-dot swatch-dot swatch-<?= e((string) ($i + 1)) ?>"></span>
              </label>
            <?php endforeach; ?>
          </span>
          <button class="btn btn--sm" type="submit">সংরক্ষণ করুন</button>
        </form>
      </details>
    </div>
  <?php endforeach; ?>
</div>
