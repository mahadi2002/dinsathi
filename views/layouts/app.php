<?php
/**
 * Gated app shell: left sidebar ≥900px, bottom tab bar below — this
 * audience is mostly on a phone (a ৳2.78/day product).
 * @var string $content
 */
use App\Core\Session;
use App\Core\View;
use App\Repositories\NotificationRepo;
use App\Services\SubscriptionService;
use App\Support\DateBD;

$userId    = Session::userId();
$smsActive = $userId !== null && SubscriptionService::hasSmsAccess($userId);
$today     = DateBD::today();
$notifRepo = new NotificationRepo();
$unread    = $userId !== null ? $notifRepo->unreadCount($userId) : 0;
$notifs    = $userId !== null ? $notifRepo->recentForUser($userId, 8) : [];

$sideLinks = [
    'পরিকল্পনা' => [
        ['/app',                 'হোম',        'M3 11l9-8 9 8v9a2 2 0 0 1-2 2h-4v-6H9v6H5a2 2 0 0 1-2-2z'],
        ['/app/tasks',           'সব Task',    'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
        ['/app/day/' . $today,   'দিন ভিউ',    'M4 6h16v15H4zM4 10h16M8 3v4M16 3v4'],
        ['/app/week/' . $today,  'সপ্তাহ ভিউ', 'M3 4h18v18H3zM3 10h18M9 4v18'],
        ['/app/month/' . date('Y-m') . '-01', 'মাস ভিউ', 'M4 5h16v16H4zM4 9h16M8 3v4M16 3v4'],
        ['/app/lists',           'Lists',      'M4 6h16M4 12h16M4 18h10'],
    ],
    'অভ্যাস ও মনোযোগ' => [
        ['/app/habits', 'Habit Tracker', 'M12 21c-4.5 0-8-3-8-7 0-3 2-5 4-7 1 2 2 3 2 5 1-2 1-4 0-6 3 1 6 4 6 8s-1.5 7-4 7z'],
        ['/app/focus',  'Focus Timer',   'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 7v5l3 3'],
        ['/app/review/' . $today, 'Daily Review', 'M5 4h11a3 3 0 0 1 3 3v13H8a3 3 0 0 1-3-3z M8 8h8M8 12h6'],
        ['/app/insights', 'Insights', 'M4 19h16M7 15v4M12 9v10M17 5v14'],
    ],
    'অ্যাকাউন্ট' => [
        ['/app/settings', 'Settings', 'M10.3 2h3.4l.7 2.7a8 8 0 0 1 2 1.2l2.6-.9 1.7 3-2 1.9a8 8 0 0 1 0 2.2l2 1.9-1.7 3-2.6-.9a8 8 0 0 1-2 1.2l-.7 2.7h-3.4l-.7-2.7a8 8 0 0 1-2-1.2l-2.6.9-1.7-3 2-1.9a8 8 0 0 1 0-2.2l-2-1.9 1.7-3 2.6.9a8 8 0 0 1 2-1.2z'],
    ],
];

$tabs = [
    ['/app',                'হোম',   'M3 11l9-8 9 8v9a2 2 0 0 1-2 2h-4v-6H9v6H5a2 2 0 0 1-2-2z'],
    ['/app/tasks',          'Task',  'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
    ['/app/day/' . $today,  'দিন',   'M4 6h16v15H4zM4 10h16M8 3v4M16 3v4'],
    ['/app/habits', 'Habit', 'M12 21c-4.5 0-8-3-8-7 0-3 2-5 4-7 1 2 2 3 2 5 1-2 1-4 0-6 3 1 6 4 6 8s-1.5 7-4 7z'],
    ['/app/settings',       'Settings', 'M10.3 2h3.4l.7 2.7a8 8 0 0 1 2 1.2l2.6-.9 1.7 3-2 1.9a8 8 0 0 1 0 2.2l2 1.9-1.7 3-2.6-.9a8 8 0 0 1-2 1.2l-.7 2.7h-3.4l-.7-2.7a8 8 0 0 1-2-1.2l-2.6.9-1.7-3 2-1.9a8 8 0 0 1 0-2.2l-2-1.9 1.7-3 2.6.9a8 8 0 0 1 2-1.2z'],
];

$isCurrent = static function (string $href) use ($currentPath): bool {
    return $href === '/app' ? $currentPath === '/app' : str_starts_with($currentPath, explode('?', $href)[0]);
};
?>
<!doctype html>
<html lang="bn" data-theme="<?= e($theme ?? 'light') ?>">
<head>
<?= View::partial('partials/head', get_defined_vars()) ?>
<script type="application/json" id="push-config"><?= json_encode(['vapidPublicKey' => (string) config('push.vapid_public')], JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body>
<a class="skip-link" href="#main">মূল অংশে যান</a>

<div class="app-shell" data-fab-open="false">
  <header class="app-topbar">
    <div class="wrap app-topbar__inner">
      <a class="logo" href="/app">
        <?= View::partial('partials/logo') ?>
        <span><?= e($appName) ?></span>
      </a>

      <nav class="nav" aria-label="অ্যাকাউন্ট">
        <?php if (!$smsActive): ?><a class="btn btn--accent btn--sm" href="/subscribe/sms">+ SMS Reminder</a><?php endif; ?>

        <div class="bell-wrap">
          <button class="theme-toggle bell-btn" type="button" data-bell-toggle aria-label="Notifications">
            🔔<?php if ($unread > 0): ?><span class="bell-dot" aria-hidden="true"></span><?php endif; ?>
          </button>
          <div class="card bell-panel" data-bell-panel hidden>
            <p class="card__title text-step-0">Notifications</p>
            <?php if ($notifs === []): ?>
              <p class="small muted mb-0">কোনো Notification নেই।</p>
            <?php else: ?>
              <ul class="notif-list">
                <?php foreach ($notifs as $n): ?>
                  <li class="<?= $n['read_at'] === null ? 'is-unread' : '' ?>">
                    <strong class="small"><?= e((string) $n['title']) ?></strong>
                    <div class="small muted"><?= e((string) $n['body']) ?></div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>

        <button class="theme-toggle" type="button" data-theme-toggle
                aria-label="<?= ($theme ?? 'light') === 'dark' ? 'দিনের রঙে দেখুন' : 'রাতের রঙে দেখুন' ?>">
          <?= ($theme ?? 'light') === 'dark' ? '☀' : '☾' ?>
        </button>
        <form method="post" action="/logout" data-guard>
          <?= csrf_field() ?>
          <button class="btn btn--ghost btn--sm" type="submit">Logout</button>
        </form>
      </nav>
    </div>
  </header>

  <div class="app-body">
    <aside class="app-sidebar">
      <?php foreach ($sideLinks as $group => $items): ?>
        <?php if ($items === []) { continue; } ?>
        <div class="side-nav">
          <p class="group-label"><?= e($group) ?></p>
          <?php foreach ($items as [$href, $label, $path]): ?>
            <a href="<?= e($href) ?>"<?= $isCurrent($href) ? ' aria-current="page"' : '' ?>>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                   stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?= e($path) ?>"/></svg>
              <span><?= e($label) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>

      <?php if (!$smsActive): ?>
        <div class="card sidebar-upsell">
          <p class="small mb-0">Push-এর পাশাপাশি SMS-এও Reminder পেতে —</p>
          <a class="btn btn--accent btn--sm mt-sm" href="/subscribe/sms">SMS Add-on ৳<?= e($smsAmount) ?>/day</a>
        </div>
      <?php endif; ?>
    </aside>

    <main class="app-main" id="main">
      <?= View::partial('partials/flash', ['notice' => $notice ?? null]) ?>
      <?= $content ?>
    </main>
  </div>

  <nav class="tabbar" aria-label="দ্রুত মেনু">
    <?php foreach ($tabs as [$href, $label, $path]): ?>
      <a href="<?= e($href) ?>"<?= $isCurrent($href) ? ' aria-current="page"' : '' ?>>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?= e($path) ?>"/></svg>
        <span><?= e($label) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="fab-backdrop" data-fab-backdrop></div>
  <div class="fab-group" data-fab-group data-open="false">
    <ul class="fab-menu">
      <li>
        <a href="/app/tasks#quick-add">
          <span class="fab-menu__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></span>
          <span>নতুন Task</span>
        </a>
      </li>
      <li>
        <a href="/app/habits">
          <span class="fab-menu__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21c-4.5 0-8-3-8-7 0-3 2-5 4-7 1 2 2 3 2 5 1-2 1-4 0-6 3 1 6 4 6 8s-1.5 7-4 7z"/></svg></span>
          <span>নতুন Habit</span>
        </a>
      </li>
    </ul>
    <button class="fab" type="button" data-fab-toggle aria-expanded="false" aria-haspopup="true" aria-label="দ্রুত কাজ">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
    </button>
  </div>
</div>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php foreach (($scripts ?? []) as $script): ?>
  <script src="<?= e(asset('js/' . $script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
