<?php

declare(strict_types=1);

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Logged-in portal UI — extends landing design language.
 */
class PortalAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/portal.css',
    ];
    public $js = [
        'js/portal.js',
    ];
    public $depends = [
        AppAsset::class,
    ];
}
