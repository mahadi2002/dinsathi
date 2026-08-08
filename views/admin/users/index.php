<?php
/** @var array $users @var int $total @var int $page @var int $perPage @var string $search */
$this->layout('layouts/admin', ['title' => 'Users']);
$pages = (int) ceil($total / $perPage);
?>
<h1>Users</h1>

<form method="get" action="/admin/users" class="cluster mb-md">
  <input type="text" name="q" value="<?= e($search) ?>" placeholder="Mobile number দিয়ে খুঁজুন..." class="field-narrow">
  <button class="btn btn--sm" type="submit">Search</button>
</form>

<div class="table-wrap">
  <table>
    <thead><tr><th>ID</th><th>Mobile</th><th>Operator</th><th>Status</th><th>Registered</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= e((string) $u['id']) ?></td>
          <td><?= e(mask_msisdn((string) $u['mobile_number'])) ?></td>
          <td><?= e((string) $u['operator']) ?></td>
          <td><?= e((string) $u['status']) ?></td>
          <td><?= e(bn_date_utc((string) $u['created_at'])) ?></td>
          <td><a class="small" href="/admin/users/<?= e((string) $u['id']) ?>">বিস্তারিত</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($users === []): ?><tr><td colspan="6" class="muted">কোনো User পাওয়া যায়নি।</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($pages > 1): ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
      <a class="btn btn--sm <?= $p === $page ? 'btn--primary' : 'btn--ghost' ?>" href="/admin/users?page=<?= e((string) $p) ?>&q=<?= e($search) ?>"><?= e((string) $p) ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>
