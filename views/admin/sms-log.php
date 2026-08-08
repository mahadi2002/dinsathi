<?php
/** @var array $logs @var int $total @var int $page @var int $perPage @var float $deliveryRate */
$this->layout('layouts/admin', ['title' => 'SMS Delivery Log']);
$pages = (int) ceil($total / $perPage);
?>
<h1>SMS Delivery Log</h1>
<p class="muted">Delivery Rate: <strong><?= e((string) $deliveryRate) ?>%</strong></p>

<div class="table-wrap">
  <table>
    <thead><tr><th>Mobile</th><th>Message</th><th>Gateway</th><th>Status</th><th>Time</th></tr></thead>
    <tbody>
      <?php foreach ($logs as $log): ?>
        <tr>
          <td><?= e(mask_msisdn((string) $log['mobile_number'])) ?></td>
          <td class="small"><?= e(str_excerpt((string) $log['message'], 60)) ?></td>
          <td><?= e((string) $log['gateway']) ?></td>
          <td><span class="chip <?= $log['status'] === 'sent' ? 'chip--success' : 'chip--urgent' ?>"><?= e((string) $log['status']) ?></span></td>
          <td><?= e(bn_date_utc((string) $log['created_at'], true)) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($logs === []): ?><tr><td colspan="5" class="muted">কোনো Log নেই।</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php if ($pages > 1): ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
      <a class="btn btn--sm <?= $p === $page ? 'btn--primary' : 'btn--ghost' ?>" href="/admin/sms-log?page=<?= e((string) $p) ?>"><?= e((string) $p) ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>
