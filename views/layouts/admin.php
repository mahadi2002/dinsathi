<?php
/** @var string $content */
use App\Core\View;

$links = [
    ['/admin', 'Dashboard'],
    ['/admin/users', 'Users'],
    ['/admin/billing-events', 'Billing Events'],
    ['/admin/sms-log', 'SMS Log'],
    ['/admin/contact', 'Contact Inbox'],
    ['/admin/broadcast', 'Broadcast'],
    ['/admin/logs', 'App Logs'],
];
?>
<!doctype html>
<html lang="bn" data-theme="<?= e($theme ?? 'light') ?>">
<head>
<?= View::partial('partials/head', get_defined_vars()) ?>
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <a class="logo admin-brand" href="/admin">
      <?= View::partial('partials/logo') ?><span>Admin</span>
    </a>
    <?php foreach ($links as [$href, $label]): ?>
      <a href="<?= e($href) ?>"<?= $currentPath === $href ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
    <form method="post" action="/admin/logout" data-guard class="mt-xl">
      <?= csrf_field() ?>
      <button class="btn btn--ghost btn--sm btn--full admin-logout-btn" type="submit">Logout</button>
    </form>
  </aside>
  <main class="admin-main">
    <?= View::partial('partials/flash', ['notice' => $notice ?? null]) ?>
    <?= $content ?>
  </main>
</div>
</body>
</html>
