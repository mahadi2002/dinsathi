<?php
/** @var array $events @var int $total @var int $page @var int $perPage */
$this->layout('layouts/admin', ['title' => 'Billing Events']);
$pages = (int) ceil($total / $perPage);
?>
<h1>Billing Events</h1>
<div class="table-wrap">
  <table>
    <thead><tr><th>User</th><th>Plan</th><th>Type</th><th>Amount</th><th>Time</th></tr></thead>
    <tbody>
      <?php foreach ($events as $ev): ?>
        <tr>
          <td><a href="/admin/users/<?= e((string) $ev['user_id']) ?>">#<?= e((string) $ev['user_id']) ?></a></td>
          <td><?= e($ev['plan'] === 'sms_reminders' ? 'SMS Add-on' : 'Planner') ?></td>
          <td><?= e((string) $ev['event_type']) ?></td>
          <td><?= $ev['amount'] !== null ? '৳' . e(number_format((float) $ev['amount'], 2)) : '—' ?></td>
          <td><?= e(bn_date_utc((string) $ev['occurred_at'], true)) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($events === []): ?><tr><td colspan="5" class="muted">কোনো ইভেন্ট নেই।</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php if ($pages > 1): ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
      <a class="btn btn--sm <?= $p === $page ? 'btn--primary' : 'btn--ghost' ?>" href="/admin/billing-events?page=<?= e((string) $p) ?>"><?= e((string) $p) ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>
