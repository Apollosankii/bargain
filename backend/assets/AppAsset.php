<?php

declare(strict_types=1);

namespace backend\assets;

use common\assets\ColorModeAsset;
use yii\bootstrap5\BootstrapAsset;
use yii\web\AssetBundle;
use yii\web\YiiAsset;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/site.css',
        'css/admin.css',
    ];
    public $js = [
        'js/admin.js',
    ];
    public $depends = [
        YiiAsset::class,
        BootstrapAsset::class,
        ColorModeAsset::class,
    ];
}
