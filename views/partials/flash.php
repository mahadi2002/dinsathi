<?php
/** @var array|null $notice */
if (empty($notice)) {
    return;
}
$type = (string) ($notice['type'] ?? 'info');
$icon = match ($type) {
    'success' => '✓',
    'error'   => '!',
    default   => 'ⓘ',
};
?>
<div class="notice notice--<?= e($type) ?>" role="status">
  <span class="notice__icon" aria-hidden="true"><?= e($icon) ?></span>
  <span><?= e((string) $notice['text']) ?></span>
</div>
