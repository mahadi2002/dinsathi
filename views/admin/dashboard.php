<?php
/** @var int $totalUsers @var int $plannerActive @var int $plannerSuspended @var int $plannerExpired
 * @var int $smsActive @var int $smsSuspended @var int $smsExpired
 * @var float $mrrProxy @var float $smsDeliveryRate @var array $recentAudit */
$this->layout('layouts/admin', ['title' => 'Admin Dashboard']);
?>
<h1>Dashboard</h1>

<div class="grid grid--4 mb-xl">
  <div class="stat-tile"><div class="stat-tile__n"><?= e((string) $totalUsers) ?></div><div class="stat-tile__l">Total Users</div></div>
  <div class="stat-tile"><div class="stat-tile__n"><?= e((string) $plannerActive) ?></div><div class="stat-tile__l">Active Planner Subs</div></div>
  <div class="stat-tile"><div class="stat-tile__n"><?= e((string) $smsActive) ?></div><div class="stat-tile__l">Active SMS Add-ons</div></div>
  <div class="stat-tile"><div class="stat-tile__n">৳<?= e(number_format($mrrProxy, 0)) ?></div><div class="stat-tile__l">MRR Proxy (30-day)</div></div>
</div>

<div class="grid grid--2">
  <div class="card">
    <h2 class="card__title">Subscription Breakdown</h2>
    <table>
      <thead><tr><th></th><th>Planner</th><th>SMS Add-on</th></tr></thead>
      <tr><th>Active</th><td><?= e((string) $plannerActive) ?></td><td><?= e((string) $smsActive) ?></td></tr>
      <tr><th>Suspended</th><td><?= e((string) $plannerSuspended) ?></td><td><?= e((string) $smsSuspended) ?></td></tr>
      <tr><th>Expired</th><td><?= e((string) $plannerExpired) ?></td><td><?= e((string) $smsExpired) ?></td></tr>
    </table>
    <p class="small muted mt-sm mb-0">SMS Delivery Rate: <?= e((string) $smsDeliveryRate) ?>%</p>
  </div>

  <div class="card">
    <h2 class="card__title">সাম্প্রতিক Admin Activity</h2>
    <?php if ($recentAudit === []): ?>
      <p class="muted mb-0">কোনো Activity নেই।</p>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Admin</th><th>Action</th><th>Time</th></tr></thead>
          <tbody>
            <?php foreach ($recentAudit as $a): ?>
              <tr><td><?= e((string) ($a['admin_email'] ?? '—')) ?></td><td><?= e((string) $a['action']) ?></td><td><?= e(bn_date_utc((string) $a['created_at'], true)) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
