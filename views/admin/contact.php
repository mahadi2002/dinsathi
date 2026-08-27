<?php
/** @var array $messages @var int $total @var int $page @var int $perPage @var ?string $status @var int $newCount */
$this->layout('layouts/admin', ['title' => 'Contact Inbox']);
$pages = (int) ceil($total / $perPage);
?>
<div class="between">
  <h1 class="mb-0">Contact Inbox</h1>
  <span class="chip <?= $newCount > 0 ? 'chip--urgent' : '' ?>"><?= e((string) $newCount) ?> নতুন</span>
</div>
<p class="muted">Contact Form থেকে আসা বার্তাগুলো এখানে দেখুন এবং সমাধান হলে চিহ্নিত করুন।</p>

<div class="cluster mb-md">
  <a class="btn btn--sm <?= $status === null ? 'btn--primary' : 'btn--ghost' ?>" href="/admin/contact">সব</a>
  <a class="btn btn--sm <?= $status === 'new' ? 'btn--primary' : 'btn--ghost' ?>" href="/admin/contact?status=new">নতুন</a>
  <a class="btn btn--sm <?= $status === 'resolved' ? 'btn--primary' : 'btn--ghost' ?>" href="/admin/contact?status=resolved">সমাধান হয়েছে</a>
</div>

<div class="table-wrap">
  <table>
    <thead><tr><th>নাম</th><th>যোগাযোগ</th><th>বার্তা</th><th>স্ট্যাটাস</th><th>সময়</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($messages as $m): ?>
        <tr>
          <td><?= e((string) $m['name']) ?></td>
          <td><?= e((string) $m['contact']) ?></td>
          <td class="small"><?= e(str_excerpt((string) $m['message'], 80)) ?></td>
          <td><span class="chip <?= $m['status'] === 'new' ? 'chip--urgent' : 'chip--success' ?>"><?= $m['status'] === 'new' ? 'নতুন' : 'সমাধান হয়েছে' ?></span></td>
          <td><?= e(bn_date_utc((string) $m['created_at'], true)) ?></td>
          <td>
            <?php if ($m['status'] === 'new'): ?>
              <form method="post" action="/admin/contact/<?= e((string) $m['id']) ?>/resolve" data-guard>
                <?= csrf_field() ?>
                <button class="btn btn--sm btn--ghost" type="submit">সমাধান হয়েছে ✓</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if ($messages === []): ?><tr><td colspan="6" class="muted">কোনো বার্তা নেই।</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php if ($pages > 1): ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
      <a class="btn btn--sm <?= $p === $page ? 'btn--primary' : 'btn--ghost' ?>" href="/admin/contact?page=<?= e((string) $p) ?><?= $status !== null ? '&status=' . e($status) : '' ?>"><?= e((string) $p) ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>
