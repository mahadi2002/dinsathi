<?php
/** @var int|null $retryAfter */
$this->layout('layouts/public', ['title' => 'অনেকবার চেষ্টা হয়েছে']);
?>
<div class="section center">
  <p class="eyebrow">৪২৯</p>
  <h1>অনেকবার চেষ্টা হয়েছে</h1>
  <p class="lede center">
    <?= $retryAfter !== null ? e(\App\Core\RateLimit::humanWait((int) $retryAfter)) . ' পর আবার চেষ্টা করুন।' : 'একটু পরে আবার চেষ্টা করুন।' ?>
  </p>
  <a class="btn btn--primary" href="/">হোমে ফিরে যান</a>
</div>
