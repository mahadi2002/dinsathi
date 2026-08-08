<?php
declare(strict_types=1);

// SINGLE SOURCE OF TRUTH. Update here only.
//
// Per BTRC allocation, 018 is Robi's own range and 016 is Airtel's own
// range — matches the prefix map already used by this workspace's other
// apps (gardenbondhu, ielts-master-bd).
return [
    'allowed' => ['robi', 'airtel'],
    'map' => [
        '018' => 'robi',
        '016' => 'airtel',
    ],
    'display_note' => 'শুধু Robi (018) ও Airtel (016) Number',
];
