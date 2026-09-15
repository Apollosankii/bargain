<?php

declare(strict_types=1);

return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@bargain.local',
    'senderEmail' => 'noreply@bargain.local',
    'senderName' => 'Bargain',
    // Used for absolute links in emails (including console cron).
    'frontendBaseUrl' => 'http://localhost:3000',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 6,
    // Defaults; real secrets live in params-local.php (gitignored)
    'mpesa' => [
        'env' => 'sandbox',
        'consumerKey' => '',
        'consumerSecret' => '',
        'shortcode' => '',
        'passkey' => '',
        'callbackUrl' => '',
    ],
    // KES amounts for paid auctioneer plans (free trial is 0).
    'subscriptionPrices' => [
        '1_MONTH' => 500,
        '6_MONTHS' => 2500,
        '12_MONTHS' => 4500,
    ],
];
