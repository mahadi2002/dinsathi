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
<div class="auth-shell">
  <main id="main" class="auth-card">
    <a class="logo" href="/"><?= View::partial('partials/logo') ?><span><?= e($appName) ?></span></a>
    <?php if (!empty($notice)): ?><?= View::partial('partials/flash', ['notice' => $notice]) ?><?php endif; ?>
    <?= $content ?>
  </main>
</div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
