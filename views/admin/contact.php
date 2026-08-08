<?php
/** @var string $tail */
$this->layout('layouts/admin', ['title' => 'Contact Inbox']);
?>
<h1>Contact Inbox</h1>
<p class="muted">Contact Form-এর বার্তাগুলো এখনো একটি সাধারণ লগ ফাইলে সংরক্ষিত হয়, আলাদা Inbox নেই। নিচে আজকের বার্তাগুলো দেখানো হচ্ছে।</p>
<div class="card">
  <pre class="small log-pre"><?= $tail !== '' ? e($tail) : '(আজ কোনো বার্তা আসেনি)' ?></pre>
</div>
