<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Dashboard';
$name = Yii::$app->user->identity?->first_name ?? 'Admin';
?>
<div class="admin-page">
    <div class="admin-welcome">
        <h1 class="admin-welcome__title">Welcome back, <?= Html::encode($name) ?></h1>
        <p class="admin-welcome__text">Your command center for managing users, auctions, payments, and moderation.</p>
    </div>

    <div class="admin-quick-grid">
        <div class="admin-quick-card">
            <div>
                <div class="admin-quick-card__label">Platform</div>
                <div class="admin-quick-card__title">Manage users &amp; auctions</div>
            </div>
            <a class="btn btn-primary btn-sm" href="<?= Url::to(['/admin/index']) ?>">Open</a>
        </div>
        <div class="admin-quick-card">
            <div>
                <div class="admin-quick-card__label">Finance</div>
                <div class="admin-quick-card__title">Review payment status</div>
            </div>
            <a class="btn btn-outline-primary btn-sm" href="<?= Url::to(['/payment/index']) ?>">Open</a>
        </div>
        <div class="admin-quick-card">
            <div>
                <div class="admin-quick-card__label">Moderation</div>
                <div class="admin-quick-card__title">Review complaints</div>
            </div>
            <a class="btn btn-outline-primary btn-sm" href="<?= Url::to(['/complaint/index']) ?>">Open</a>
        </div>
        <div class="admin-quick-card">
            <div>
                <div class="admin-quick-card__label">Audit</div>
                <div class="admin-quick-card__title">View admin action log</div>
            </div>
            <a class="btn btn-outline-primary btn-sm" href="<?= Url::to(['/audit/index']) ?>">Open</a>
        </div>
    </div>
</div>
