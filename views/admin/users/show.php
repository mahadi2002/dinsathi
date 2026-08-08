<?php
/** @var array $user @var array $subs @var int $taskCount @var int $habitCount */
$this->layout('layouts/admin', ['title' => 'User #' . $user['id']]);
?>
<a href="/admin/users" class="small">← Users</a>
<h1>User #<?= e((string) $user['id']) ?></h1>

<div class="grid grid--2">
  <div class="card">
    <h2 class="card__title">Profile</h2>
    <table>
      <tr><th>Mobile</th><td><?= e((string) $user['mobile_number']) ?></td></tr>
      <tr><th>Operator</th><td><?= e((string) $user['operator']) ?></td></tr>
      <tr><th>Status</th><td><?= e((string) $user['status']) ?></td></tr>
      <tr><th>Registered</th><td><?= e(bn_date_utc((string) $user['created_at'], true)) ?></td></tr>
      <tr><th>Tasks</th><td><?= e((string) $taskCount) ?></td></tr>
      <tr><th>Habits</th><td><?= e((string) $habitCount) ?></td></tr>
    </table>
    <p class="small muted mt-sm">এই পূর্ণ নম্বর দেখার কারণে এই দর্শনটি Audit Log-এ রেকর্ড হয়েছে।</p>
  </div>

  <div class="card">
    <h2 class="card__title">Subscription History</h2>
    <?php if ($subs === []): ?>
      <p class="muted mb-0">কোনো Subscription নেই।</p>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Plan</th><th>Status</th><th>Started</th><th>Ended</th><th>Ref</th></tr></thead>
          <tbody>
            <?php foreach ($subs as $s): ?>
              <tr><td><?= e($s['plan'] === 'sms_reminders' ? 'SMS Add-on' : 'Planner') ?></td><td><?= e((string) $s['status']) ?></td><td><?= e(bn_date_utc((string) $s['started_at'])) ?></td><td><?= e($s['ended_at'] ? bn_date_utc((string) $s['ended_at']) : '—') ?></td><td class="small"><?= e((string) ($s['external_ref'] ?? '—')) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
