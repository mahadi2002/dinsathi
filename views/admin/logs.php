<?php
/** @var string $tail */
$this->layout('layouts/admin', ['title' => 'App Logs']);
?>
<h1>App Logs (আজকের)</h1>
<div class="card">
  <pre class="small log-pre log-pre--tall"><?= $tail !== '' ? e($tail) : '(আজ কোনো লগ নেই)' ?></pre>
</div>
