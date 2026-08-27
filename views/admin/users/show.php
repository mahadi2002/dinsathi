<?php
/** @var array $user @var int $taskCount @var int $habitCount */
$this->layout('layouts/admin', ['title' => 'User #' . $user['id']]);
?>
<a href="/admin/users" class="small">← Users</a>
<h1>User #<?= e((string) $user['id']) ?></h1>

<div class="card">
  <h2 class="card__title">Profile</h2>
  <table>
    <tr><th>Email</th><td><?= e((string) $user['email']) ?></td></tr>
    <tr><th>Status</th><td><?= e((string) $user['status']) ?></td></tr>
    <tr><th>Registered</th><td><?= e(bn_date_utc((string) $user['created_at'], true)) ?></td></tr>
    <tr><th>Tasks</th><td><?= e((string) $taskCount) ?></td></tr>
    <tr><th>Habits</th><td><?= e((string) $habitCount) ?></td></tr>
  </table>
  <p class="small muted mt-sm">এই দর্শনটি Audit Log-এ রেকর্ড হয়েছে।</p>
</div>
