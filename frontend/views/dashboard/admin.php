<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'Admin Dashboard — Bargain';
/** @var \common\models\User $user */
$user = Yii::$app->user->identity;
?>
<div class="container" style="padding:2rem 1.5rem; max-width:1100px; margin:0 auto;">
    <h1 style="font-size:1.5rem; margin-bottom:0.35rem;">
        Admin — <?= Html::encode($user->first_name) ?>
    </h1>
    <p style="color:var(--text-secondary); margin-bottom:1.5rem;">
        User and auction moderation will land here.
    </p>
</div>
