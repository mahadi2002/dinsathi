<?php
/**
 * Landing page. Section order and a few exact copy blocks (mid-page CTA,
 * OTP box) are locked per the build spec — do not paraphrase.
 */
use App\Core\View;

$this->layout('layouts/public', [
    'title'       => null,
    'description' => $description ?? null,
]);

$features = [
    ['Task ও To-Do',       'Priority, Due Date/Time দিয়ে যত খুশি Task তৈরি করুন — Subtask ও Checklist সহ'],
    ['Recurring Task',     'রোজ, প্রতি সপ্তাহে বা মাসে — একবার সেট করুন, বাকিটা DinSathi সামলাবে'],
    ['Lists, Tags ও খোঁজ', 'কাজ, বাসা, পড়াশোনা — নিজের মতো করে রঙিন List ও Tag দিয়ে সাজান, নাম দিয়ে সাথে সাথে খুঁজে বের করুন'],
    ['Push + SMS Reminder', 'কোনো কাজ মিস হবে না — Notification এবং SMS দুইভাবেই মনে করিয়ে দেয়া হয়'],
    ['Calendar View',      'দিন, সপ্তাহ বা মাস — যেভাবে খুশি আপনার প্ল্যান দেখুন'],
    ['Habit Tracker',      'প্রতিদিনের অভ্যাস Track করুন, Streak ধরে রাখুন — শান্ত, চাপহীন উপায়ে'],
    ['Focus Timer',        'Pomodoro-স্টাইল Timer দিয়ে মনোযোগ ধরে রাখুন, সময় Log করুন'],
    ['Daily Review',       'দিন শেষে ছোট্ট একটা Reflection — কেমন গেল দিনটা, লিখে রাখুন'],
    ['Insights ও Progress', 'সাপ্তাহিক ও মাসিক Task Completion, Focus সময় আর Habit Streak এক পেজে দেখুন'],
    ['Data Export',        'নিজের সব Task-এর তথ্য CSV করে নিজের কাছে রাখুন'],
];

$mistakes = [
    'সব কাজ মাথায় রাখার চেষ্টা করা — লিখে না রাখলে ভুলে যাওয়াটাই স্বাভাবিক।',
    'একটাই to-do list-এ কাজ আর অভ্যাস গুলিয়ে ফেলা।',
    'Reminder ছাড়া শুধু মনে রাখার ভরসায় থাকা।',
    'একবারে অনেক কাজ প্ল্যান করে ফেলা, পরে চাপে ফেলে দেওয়া।',
    'দিন শেষে ফিরে না দেখা — কী হলো, কী বাকি রইল।',
    'অভ্যাস গড়ার চেষ্টা করা কোনো Streak বা Track ছাড়াই।',
];

$whySubscribe = [
    ['🧠', 'মাথা থেকে চাপ কমান', 'সব কাজ, তারিখ আর অভ্যাস একজায়গায় গোছানো থাকলে আলাদা করে মনে রাখার দরকার পড়ে না — মাথাটা হালকা থাকে।'],
    ['⏰', 'কখনও Deadline মিস হবে না', 'Push Notification-এর পাশাপাশি SMS Reminder Add-on নিলে Internet না থাকলেও Reminder পৌঁছাবে সরাসরি আপনার ফোনে।'],
    ['🔥', 'ধারাবাহিকভাবে অভ্যাস গড়ুন', 'Habit Tracker আর Streak দেখে প্রতিদিন একটু একটু এগিয়ে যাওয়ার তাগিদ পাবেন — একদিন বাদ পড়লেও শুরু থেকে হতাশ হতে হবে না।'],
    ['📊', 'নিজের Progress দেখুন', 'Insights পেজে সপ্তাহ ও মাস ধরে কতগুলো কাজ শেষ করলেন, কতক্ষণ Focus করলেন — সবটা এক নজরে বুঝে নিন।'],
    ['☕', 'এক কাপ চায়ের চেয়েও কম খরচ', 'Daily ৳' . $dailyAmount . ' (Incl. VAT, SD & SC)-তে সারাদিনের পরিকল্পনা গোছানো — মাসে যা খরচ, তার চেয়ে বেশি সময় আর মনোযোগ বাঁচবে।'],
    ['🔓', 'কোনো Lock-in নেই', 'Card বা bKash লাগে না — মোবাইল ব্যালেন্স থেকেই কাটে, আর যেকোনো সময় এক SMS বা Settings থেকে সাথে সাথে বন্ধ করতে পারবেন।'],
];

$steps = [
    ['১) নম্বর দিন ও Verify করুন', 'Robi বা Airtel নম্বর লিখুন, SMS-এ আসা ৬ সংখ্যার OTP Code বসিয়ে Account তৈরি করুন — এক মিনিটেরও কম সময় লাগে।'],
    ['২) Planner Subscribe করুন', 'Account খোলার পরপরই Daily ৳' . $dailyAmount . ' (Incl. VAT, SD & SC)-তে Planner Subscribe করুন — Task, Recurring Task, Lists, Push Reminder, Habit Tracker, Focus Timer, Daily Review, Insights, সবকিছু তখনই খুলে যাবে। DinSathi-এর কোনো Feature Subscribe ছাড়া ব্যবহার করা যায় না।'],
    ['৩) চাইলে SMS Reminder Add-on নিন', 'Push Notification প্রতিটি Plan-এর সাথেই থাকে। Internet না থাকলেও Reminder যেন মিস না হয়, সেজন্য Settings থেকে আলাদাভাবে SMS Reminder Add-on (Daily ৳' . $smsAmount . ') চালু করতে পারেন — এটা সম্পূর্ণ ঐচ্ছিক।'],
    ['৪) প্ল্যান করা শুরু করুন', 'প্রথম List আর Task যোগ করুন, Due Date/Reminder সেট করুন — Calendar-এ দিন/সপ্তাহ/মাস ভিউতে পুরো প্ল্যান দেখুন।'],
    ['৫) অভ্যাস আর Progress Track করুন', 'Habit Tracker-এ প্রতিদিন Check-in করুন, দিনশেষে Daily Review লিখুন, আর Insights পেজে নিজের উন্নতি দেখুন।'],
];

$faqs = [
    ['Subscribe ছাড়া কি ব্যবহার করা যায়?',
     'না। DinSathi-এর কোনো Free Version নেই — Planner ব্যবহার করতে Daily ৳' . $dailyAmount . ' (Incl. VAT, SD & SC)-তে Subscribe করতে হয়। তবে যেকোনো সময় Unsubscribe করা যায়, কোনো Lock-in বা Contract নেই।'],
    ['টাকা কীভাবে কাটা হবে?',
     'Daily ৳' . $dailyAmount . ' (Incl. VAT, SD & SC) আপনার Robi/Airtel ব্যালেন্স থেকে। আলাদা কার্ড বা bKash লাগবে না।'],
    ['SMS Reminder Add-on কি বাধ্যতামূলক?',
     'না, এটা ঐচ্ছিক। Planner Subscribe করলেই Push Notification দিয়ে সব Reminder পাবেন। Internet না থাকলেও Reminder পেতে চাইলে Daily ৳' . $smsAmount . ' (Incl. VAT, SD & SC)-তে আলাদাভাবে SMS Reminder Add-on চালু করা যায়।'],
    ['বন্ধ করব কীভাবে?',
     'STOP লিখে ' . $shortcode . ' নম্বরে SMS করুন, অথবা Settings পেজ থেকে Planner বা SMS Add-on আলাদাভাবে Unsubscribe করুন। সাথে সাথেই বন্ধ হবে।'],
    ['Grameenphone/Banglalink দিয়ে হবে?',
     'এখন শুধু Robi ও Airtel। অন্য অপারেটর শীঘ্রই আসছে।'],
    ['আমার নম্বর কি অন্য কাউকে দেওয়া হবে?',
     'না। নম্বর শুধু Subscription যাচাইয়ের জন্য ব্যবহৃত হয়, কারো সাথে শেয়ার করা হয় না।'],
];
?>

<!-- HERO ------------------------------------------------------------->
<section class="hero">
  <div class="wrap hero__inner">
    <div>
      <p class="eyebrow">দৈনন্দিন পরিকল্পনার সাথী · বাংলায়</p>
      <h1>কোনো কাজ যেন আর মিস না হয়</h1>
      <p class="lede">
        Task, Reminder, Habit — সব একসাথে, একটাই জায়গায়। DinSathi আপনাকে সময়মতো মনে করিয়ে দেবে,
        Push Notification এবং SMS দুইভাবেই।
      </p>
      <p class="cluster">
        <a class="btn btn--accent btn--lg" href="#otp-box">Subscribe Now</a>
        <a class="btn btn--ghost btn--lg" href="#features">Feature দেখি</a>
      </p>
      <p class="small muted">Robi &amp; Airtel Users Only &nbsp;|&nbsp; যেকোনো সময় Unsubscribe করুন</p>
    </div>

    <div class="hero__art">
      <svg viewBox="0 0 320 320" role="img" aria-label="দিনের কাজ সম্পন্ন হওয়ার রিং">
        <circle cx="160" cy="160" r="130" fill="none" stroke="var(--line)" stroke-width="1"/>
        <circle cx="160" cy="160" r="104" fill="none" stroke="var(--paper-dim)" stroke-width="20"/>
        <circle cx="160" cy="160" r="104" fill="none" stroke="url(#hero-ring)" stroke-width="20"
                stroke-linecap="round" stroke-dasharray="530 653" transform="rotate(-90 160 160)"/>
        <text x="160" y="152" text-anchor="middle" font-size="34" font-weight="700" fill="var(--ink)" font-family="var(--font-display)">৬/৮</text>
        <text x="160" y="180" text-anchor="middle" font-size="14" fill="var(--ink-soft)" font-family="var(--font-body)">আজকের কাজ</text>
        <defs>
          <linearGradient id="hero-ring" x1="56" y1="56" x2="264" y2="264" gradientUnits="userSpaceOnUse">
            <stop stop-color="#1E2760"/><stop offset="1" stop-color="#4E5CB8"/>
          </linearGradient>
        </defs>
      </svg>
    </div>
  </div>
</section>

<!-- PROBLEM ---------------------------------------------------------->
<section class="section section--tight">
  <div class="wrap reveal">
    <h2>পরিকল্পনায় ৬টি সাধারণ ভুল</h2>
    <p class="lede">এর মধ্যে অন্তত দুটো আপনিও করছেন। খারাপ কিছু না — সঠিক টুল ছিল না, এই যা।</p>

    <ul class="pain-list">
      <?php foreach ($mistakes as $i => $mistake): ?>
        <li><span class="mark"><?= e(bn_num($i + 1)) ?></span><span><?= e($mistake) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- WHY SUBSCRIBE ------------------------------------------------------>
<section class="section section--tight">
  <div class="wrap">
    <div class="reveal">
      <p class="eyebrow">কেন খরচ করে Subscribe করবেন</p>
      <h2>যা যা সরাসরি উপকারে আসবে</h2>
      <p class="lede">দিনে মাত্র কয়েক টাকায় যা পাচ্ছেন, তার তুলনায় বাঁচানো সময় আর মানসিক শান্তি অনেক বেশি।</p>
    </div>

    <div class="grid grid--3">
      <?php foreach ($whySubscribe as [$icon, $whyTitle, $whyText]): ?>
        <article class="card feature-card reveal">
          <p class="num" aria-hidden="true"><?= e($icon) ?></p>
          <h3 class="card__title"><?= e($whyTitle) ?></h3>
          <p class="small mb-0"><?= e($whyText) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FEATURES ----------------------------------------------------------->
<section class="section" id="features">
  <div class="wrap">
    <div class="reveal">
      <p class="eyebrow">কী কী পাবেন</p>
      <h2>দিন পরিকল্পনার প্রতিটা ধাপে</h2>
    </div>

    <div class="grid grid--3">
      <?php foreach ($features as $i => [$featureTitle, $featureText]): ?>
        <article class="card feature-card reveal">
          <p class="num"><?= e(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)) ?></p>
          <h3 class="card__title"><?= e($featureTitle) ?></h3>
          <p class="small mb-0"><?= e($featureText) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- PRICING (two-tier) --------------------------------------------->
<section class="section section--tight">
  <div class="wrap">
    <div class="center reveal">
      <p class="eyebrow">খরচ — কোনো লুকানো চার্জ নেই</p>
      <h2>দুইটা সহজ Plan</h2>
      <p class="lede">Planner ব্যবহার করতে Subscribe বাধ্যতামূলক। SMS Reminder একটা ঐচ্ছিক Add-on — চাইলে পরে যেকোনো সময় নিতে পারবেন।</p>
    </div>

    <div class="grid grid--2">
      <div class="card pricing-card reveal">
        <p class="eyebrow">আবশ্যক</p>
        <h3 class="card__title">দিনসাথী Planner</h3>
        <p class="price mb-sm">৳<?= e($dailyAmount) ?><span class="small muted">/day</span></p>
        <p class="small muted">Incl. VAT, SD &amp; SC</p>
        <p class="small mb-0">Task, Recurring Task, Lists ও Tags, Push Reminder, Calendar, Habit Tracker, Focus Timer, Daily Review, Insights, Data Export — সবকিছু।</p>
      </div>
      <div class="card pricing-card reveal">
        <p class="eyebrow">ঐচ্ছিক Add-on</p>
        <h3 class="card__title">SMS Reminder</h3>
        <p class="price mb-sm">৳<?= e($smsAmount) ?><span class="small muted">/day</span></p>
        <p class="small muted">Incl. VAT, SD &amp; SC</p>
        <p class="small mb-0">Push Notification-এর পাশাপাশি Task Reminder সরাসরি SMS আকারেও পাবেন — Internet না থাকলেও কাজে আসবে। Planner Subscribe করার পর Settings থেকে যেকোনো সময় চালু/বন্ধ করা যায়।</p>
      </div>
    </div>
  </div>
</section>

<!-- CTA BLOCK (exact copy) ---------------------------------------->
<section class="section">
  <div class="wrap">
    <div class="cta-block reveal">
      <h2>🚀 এখনই Start করুন — Daily ৳<?= e($dailyAmount) ?> (Incl. VAT, SD &amp; SC)</h2>
      <p>Robi &amp; Airtel Users Only &nbsp;|&nbsp; যেকোনো সময় Unsubscribe করুন</p>

      <div class="value-copy">প্রতিদিনের কাজ, রিমাইন্ডার আর অভ্যাস — সব একসাথে, একটাই জায়গায়।
কোনো কাজ যেন আর মিস না হয়, DinSathi আপনাকে সময়মতো মনে করিয়ে দেবে —
Push Notification এবং SMS দুইভাবেই। Daily ৳<?= e($dailyAmount) ?> (Incl. VAT, SD &amp; SC), কোনো লুকানো চার্জ নেই।</div>

      <p><a class="btn btn--accent btn--lg" href="#otp-box">Subscribe Now</a></p>
    </div>
  </div>
</section>

<!-- HOW IT WORKS --------------------------------------------------------->
<section class="section section--tight">
  <div class="wrap">
    <h2 class="reveal">কীভাবে শুরু করবেন — ধাপে ধাপে</h2>
    <ol class="steps">
      <?php foreach ($steps as [$stepTitle, $stepText]): ?>
        <li class="reveal">
          <h3><?= e($stepTitle) ?></h3>
          <p class="small mb-0"><?= e($stepText) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<!-- FAQ -------------------------------------------------------------->
<section class="section">
  <div class="wrap faq">
    <h2 class="center reveal">সাধারণ প্রশ্ন</h2>
    <?php foreach ($faqs as [$question, $answer]): ?>
      <details>
        <summary><?= e($question) ?></summary>
        <div><?= e($answer) ?></div>
      </details>
    <?php endforeach; ?>
  </div>
</section>

<!-- OTP BOX (exact copy) ------------------------------------------->
<section class="section section--tight">
  <div class="wrap">
    <?= View::partial('partials/otp-box', get_defined_vars()) ?>
  </div>
</section>
