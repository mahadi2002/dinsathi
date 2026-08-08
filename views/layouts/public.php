<?php
/** @var string $content */
use App\Core\View;
?>
<!doctype html>
<html lang="bn" data-theme="<?= e($theme ?? 'light') ?>">
<head>
<?= View::partial('partials/head', get_defined_vars()) ?>
</head>
<body>
<a class="skip-link" href="#main">মূল অংশে যান</a>

<header class="site-header">
  <div class="wrap site-header__inner">
    <a class="logo" href="/">
      <?= View::partial('partials/logo') ?>
      <span><?= e($appName) ?></span>
    </a>
    <?= View::partial('partials/nav', get_defined_vars()) ?>
  </div>
</header>

<main id="main">
  <?php if (!empty($notice)): ?>
    <div class="wrap flash-slot"><?= View::partial('partials/flash', ['notice' => $notice]) ?></div>
  <?php endif; ?>

  <?= $content ?>
</main>

<?= View::partial('partials/footer', get_defined_vars()) ?>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php foreach (($scripts ?? []) as $script): ?>
  <script src="<?= e(asset('js/' . $script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
