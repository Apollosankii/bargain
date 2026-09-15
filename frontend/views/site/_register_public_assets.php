<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use frontend\assets\LandingAsset;

LandingAsset::register($this);

$this->registerLinkTag(['rel' => 'preconnect', 'href' => 'https://fonts.googleapis.com']);
$this->registerLinkTag(['rel' => 'preconnect', 'href' => 'https://fonts.gstatic.com', 'crossorigin' => '']);
$this->registerCssFile(
    'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;0,6..72,700&display=swap',
    ['depends' => [LandingAsset::class]],
);
