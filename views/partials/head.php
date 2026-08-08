<?php
/**
 * @var string|null $title
 * @var string|null $description
 * @var array $scripts
 */
$pageTitle = empty($title) ? (string) $appName . ' — দৈনন্দিন কাজ, রিমাইন্ডার ও অভ্যাস' : (string) $title . ' · ' . (string) $appName;
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($description ?? 'দৈনন্দিন কাজ, রিমাইন্ডার আর অভ্যাস — সব একসাথে, একটাই জায়গায়।') ?>">
<meta name="theme-color" content="#2E3A87">
<meta name="csrf-token" content="<?= e(\App\Core\Csrf::token()) ?>">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" type="image/svg+xml" href="/assets/img/icon.svg">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
