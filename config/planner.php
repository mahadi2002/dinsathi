<?php
declare(strict_types=1);

/** Static labels/lookups for the planner domain — not user data, just display config. */
return [
    'priority_label' => [
        'low'    => 'কম',
        'medium' => 'মাঝারি',
        'high'   => 'জরুরি',
        'urgent' => 'অতি জরুরি',
    ],
    'priority_order' => ['urgent' => 0, 'high' => 1, 'medium' => 2, 'low' => 3],

    'mood_label' => [
        'great' => 'দারুণ',
        'good'  => 'ভালো',
        'okay'  => 'মোটামুটি',
        'tough' => 'কঠিন ছিল',
    ],
    'mood_emoji' => [
        'great' => '😄',
        'good'  => '🙂',
        'okay'  => '😐',
        'tough' => '😔',
    ],

    'list_colors' => ['#2E3A87', '#F5A623', '#2E7D32', '#C62828', '#8E5FD9', '#0E7C86', '#B84A9C', '#5A6B57'],

    'reminder_offsets' => [
        0  => 'ঠিক সময়ে',
        5  => '৫ মিনিট আগে',
        15 => '১৫ মিনিট আগে',
        30 => '৩০ মিনিট আগে',
        60 => '১ ঘণ্টা আগে',
        1440 => '১ দিন আগে',
    ],
];
