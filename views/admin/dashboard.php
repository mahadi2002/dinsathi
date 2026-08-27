<?php
/** @var int $totalUsers @var array $recentAudit */
$this->layout('layouts/admin', ['title' => 'Admin Dashboard']);
?>
<h1>Dashboard</h1>

<div class="grid grid--4 mb-xl">
  <div class="stat-tile"><div class="stat-tile__n"><?= e((string) $totalUsers) ?></div><div class="stat-tile__l">Total Users</div></div>
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
