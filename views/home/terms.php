<?php $this->layout('layouts/public', ['title' => 'Terms & Conditions']); ?>
<div class="section wrap prose">
  <h1>Terms &amp; Conditions</h1>
  <p class="muted">সর্বশেষ হালনাগাদ: <?= e(bn_date(date('Y-m-d'))) ?></p>

  <h2>Subscription ও Billing</h2>
  <p>DinSathi একটি Daily Subscription Service — Daily ৳<?= e($dailyAmount) ?> (Incl. VAT, SD &amp; SC) আপনার Robi/Airtel ব্যালেন্স থেকে কাটা হয়। শুধুমাত্র Robi ও Airtel ব্যবহারকারীদের জন্য।</p>

  <h2>Unsubscribe</h2>
  <p>STOP লিখে <?= e($shortcode) ?> নম্বরে SMS করে অথবা Settings পেজ থেকে যেকোনো সময় Unsubscribe করা যায়। Unsubscribe করার সাথে সাথেই Charge বন্ধ হয়ে যায়।</p>

  <h2>ব্যবহারের শর্ত</h2>
  <p>এই Service ব্যক্তিগত পরিকল্পনার জন্য — অন্য কারো তথ্য দিয়ে Account তৈরি করা যাবে না। অপব্যবহার পেলে Account স্থগিত করা হতে পারে।</p>

  <h2>দায়বদ্ধতা</h2>
  <p>Reminder পাঠাতে যথাসাধ্য চেষ্টা করা হয়, কিন্তু Network/Device সমস্যায় কখনো Delay বা Miss হতে পারে — জরুরি কাজের জন্য একাধিক মাধ্যমে নিশ্চিত হওয়া বাঞ্ছনীয়।</p>
</div>
