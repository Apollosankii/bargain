<?php

declare(strict_types=1);

namespace frontend\assets;

use common\assets\ColorModeAsset;
use yii\web\AssetBundle;
use yii\web\YiiAsset;

/**
 * Bargain frontend assets (dark theme from bargain-api).
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/bargain.css',
        'css/bargain-app.css',
    ];
    public $depends = [
        YiiAsset::class,
        ColorModeAsset::class,
    ];
}
