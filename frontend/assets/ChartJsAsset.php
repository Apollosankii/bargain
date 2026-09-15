<?php

declare(strict_types=1);

namespace frontend\assets;

use yii\web\AssetBundle;
use yii\web\View;

class ChartJsAsset extends AssetBundle
{
    public $js = [
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
    ];
    public $jsOptions = [
        'position' => View::POS_END,
    ];
}
