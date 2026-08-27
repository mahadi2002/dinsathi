<?php
use App\Core\View;

$this->layout('layouts/public', [
    'title'       => null,
    'description' => $description ?? null,
]);

$features = [
    ['Task ও To-Do',       'Priority, Due Date/Time দিয়ে যত খুশি Task তৈরি করুন — Subtask ও Checklist সহ'],
    ['Recurring Task',     'রোজ, প্রতি সপ্তাহে বা মাসে — একবার সেট করুন, বাকিটা DinSathi সামলাবে'],
    ['Lists, Tags ও খোঁজ', 'কাজ, বাসা, পড়াশোনা — নিজের মতো করে রঙিন List ও Tag দিয়ে সাজান, নাম দিয়ে সাথে সাথে খুঁজে বের করুন'],
    ['Push Reminder',      'কোনো কাজ মিস হবে না — Browser Notification দিয়ে সময়মতো মনে করিয়ে দেয়া হয়'],
    ['Calendar View',      'দিন, সপ্তাহ বা মাস — যেভাবে খুশি আপনার প্ল্যান দেখুন'],
    ['Habit Tracker',      'প্রতিদিনের অভ্যাস Track করুন, Streak ধরে রাখুন — শান্ত, চাপহীন উপায়ে'],
    ['Focus Timer',        'Pomodoro-স্টাইল Timer দিয়ে মনোযোগ ধরে রাখুন, সময় Log করুন'],
    ['Daily Review',       'দিন শেষে ছোট্ট একটা Reflection — কেমন গেল দিনটা, লিখে রাখুন'],
    ['Insights ও Progress', 'সাপ্তাহিক ও মাসিক Task Completion, Focus সময় আর Habit Streak এক পেজে দেখুন'],
    ['Data Export',        'নিজের সব Task-এর তথ্য CSV করে নিজের কাছে রাখুন'],
];

$benefits = [
    ['🧠', 'মাথা থেকে চাপ কমান', 'সব কাজ, তারিখ আর অভ্যাস একজায়গায় গোছানো থাকলে আলাদা করে মনে রাখার দরকার পড়ে না — মাথাটা হালকা থাকে।'],
    ['⏰', 'কখনও Deadline মিস হবে না', 'Push Notification আপনাকে ঠিক সময়ে মনে করিয়ে দেবে।'],
    ['🔥', 'ধারাবাহিকভাবে অভ্যাস গড়ুন', 'Habit Tracker আর Streak দেখে প্রতিদিন একটু একটু এগিয়ে যাওয়ার তাগিদ পাবেন — একদিন বাদ পড়লেও শুরু থেকে হতাশ হতে হবে না।'],
    ['📊', 'নিজের Progress দেখুন', 'Insights পেজে সপ্তাহ ও মাস ধরে কতগুলো কাজ শেষ করলেন, কতক্ষণ Focus করলেন — সবটা এক নজরে বুঝে নিন।'],
    ['🆓', 'সম্পূর্ণ Free', 'কোনো Card, কোনো Payment, কোনো লুকানো চার্জ নেই — শুধু একটা Email দিয়ে Account খুলুন আর ব্যবহার শুরু করুন।'],
    ['🔒', 'আপনার তথ্য আপনার', 'যেকোনো সময় নিজের সব Data CSV করে Export করে নিতে পারবেন।'],
];

$steps = [
    ['১) Account তৈরি করুন', 'Email আর Password দিয়ে এক মিনিটেরও কম সময়ে Account খুলুন — সম্পূর্ণ Free, কোনো Payment লাগে না।'],
    ['২) প্রথম List আর Task যোগ করুন', 'কাজ লিখুন, Due Date/Reminder সেট করুন — Calendar-এ দিন/সপ্তাহ/মাস ভিউতে পুরো প্ল্যান দেখুন।'],
    ['৩) Push Notification চালু করুন', 'Browser Notification Allow করলে সময়মতো Reminder পাবেন, App Tab খোলা না থাকলেও।'],
    ['৪) অভ্যাস আর Progress Track করুন', 'Habit Tracker-এ প্রতিদিন Check-in করুন, দিনশেষে Daily Review লিখুন, আর Insights পেজে নিজের উন্নতি দেখুন।'],
];

$faqs = [
    ['দিনসাথী কি সম্পূর্ণ Free?',
     'হ্যাঁ। কোনো Subscription বা লুকানো চার্জ নেই — Register করলেই সব Feature ব্যবহার করতে পারবেন।'],
    ['Account খুলতে কী লাগবে?',
     'শুধু একটা Email ও একটা Password — কোনো Mobile Number বা OTP লাগবে না।'],
    ['Reminder কীভাবে পাব?',
     'Browser-এ Push Notification Allow করলে Task ও Habit-এর Reminder সরাসরি পাবেন।'],
    ['আমার Data কি নিরাপদ?',
     'আপনার তথ্য শুধু আপনার Account-এর সাথে যুক্ত থাকে এবং কারো সাথে শেয়ার করা হয় না। Settings থেকে যেকোনো সময় নিজের সব Data CSV করে Export করতে পারবেন।'],
    ['একাধিক Device থেকে ব্যবহার করা যাবে?',
     'হ্যাঁ, যেকোনো Browser থেকে নিজের Email ও Password দিয়ে Login করলেই একই Data দেখতে পাবেন।'],
];
?>

<!-- HERO ------------------------------------------------------------->
<section class="hero">
  <div class="wrap hero__inner">
    <div>
      <p class="eyebrow">দৈনন্দিন পরিকল্পনার সাথী · বাংলায়</p>
      <h1>কোনো কাজ যেন আর মিস না হয়</h1>
      <p class="lede">
        Task, Reminder, Habit — সব একসাথে, একটাই জায়গায়। DinSathi আপনাকে সময়মতো
        Push Notification দিয়ে মনে করিয়ে দেবে।
      </p>
      <p class="cluster">
        <a class="btn btn--accent btn--lg" href="/register">শুরু করুন, সম্পূর্ণ Free</a>
        <a class="btn btn--ghost btn--lg" href="#features">Feature দেখি</a>
      </p>
      <p class="small muted">কোনো Card লাগে না &nbsp;|&nbsp; যেকোনো সময় Account বন্ধ করুন</p>
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

<!-- BENEFITS ---------------------------------------------------------->
<section class="section section--tight">
  <div class="wrap">
    <div class="reveal">
      <p class="eyebrow">কেন ব্যবহার করবেন</p>
      <h2>যা যা সরাসরি উপকারে আসবে</h2>
    </div>

    <div class="grid grid--3">
      <?php foreach ($benefits as [$icon, $whyTitle, $whyText]): ?>
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

<!-- GET STARTED CTA ---------------------------------------------------->
<section class="section section--tight">
  <div class="wrap center reveal">
    <h2>শুরু করুন, সম্পূর্ণ Free</h2>
    <p class="lede center">এক মিনিটে Account খুলুন — কোনো Card বা Payment লাগে না।</p>
    <p><a class="btn btn--accent btn--lg" href="/register">Account তৈরি করুন →</a></p>
  </div>
</section>
