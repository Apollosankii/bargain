<?php

declare(strict_types=1);

$normalizeUrl = static function (string $value, string $fallback): string {
    $url = rtrim($value !== '' ? $value : $fallback, '/');
    if ($url !== '' && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
        $url = 'https://' . $url;
    }
    return $url;
};

$frontendUrl = $normalizeUrl(getenv('FRONTEND_URL') ?: '', 'http://localhost:20080');
$backendUrl = $normalizeUrl(getenv('BACKEND_URL') ?: '', 'http://localhost:21080');

return [
    'frontendBaseUrl' => $frontendUrl,
    'backendBaseUrl' => $backendUrl,
];
