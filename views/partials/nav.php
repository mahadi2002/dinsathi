<?php
/** @var string $currentPath */
$loggedIn = \App\Core\Session::userId() !== null;
?>
<nav class="nav" aria-label="প্রধান মেনু">
  <?php if ($loggedIn): ?>
    <a href="/app"<?= str_starts_with($currentPath, '/app') ? ' aria-current="page"' : '' ?>>আমার Planner</a>
  <?php else: ?>
    <a href="/login"<?= $currentPath === '/login' ? ' aria-current="page"' : '' ?>>Login</a>
    <a class="btn btn--accent btn--sm" href="/register">শুরু করুন</a>
  <?php endif; ?>
  <button class="theme-toggle" type="button" data-theme-toggle
          aria-label="<?= ($theme ?? 'light') === 'dark' ? 'দিনের রঙে দেখুন' : 'রাতের রঙে দেখুন' ?>">
    <?= ($theme ?? 'light') === 'dark' ? '☀' : '☾' ?>
  </button>
</nav>
