<?php

declare(strict_types=1);

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Public marketing assets — landing, about, auth, etc.
 */
class LandingAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/landing.css',
        'css/marketing.css',
    ];
    public $js = [
        'js/landing.js',
    ];
    public $depends = [
        AppAsset::class,
    ];
}
