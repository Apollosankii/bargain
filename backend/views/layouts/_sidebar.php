<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

if (Yii::$app->user->isGuest) {
    return;
}

/** @var \common\models\User $user */
$user = Yii::$app->user->identity;
$route = Yii::$app->controller->route;

$isActive = static function (array $routes) use ($route): string {
    foreach ($routes as $r) {
        if ($route === $r || str_starts_with($route, $r . '/')) {
            return ' is-active';
        }
    }

    return '';
};

$initials = strtoupper(substr((string) ($user->first_name ?? ''), 0, 1) . substr((string) ($user->last_name ?? ''), 0, 1));
?>
<aside class="admin-sidebar" id="admin-sidebar" aria-label="Admin navigation">
    <div class="admin-sidebar__brand">
        <a href="<?= Url::to(['/site/index']) ?>">Bargain</a>
        <span>Admin Console</span>
    </div>

    <nav class="admin-sidebar__nav">
        <div class="admin-sidebar__section">Overview</div>
        <a class="admin-sidebar__link<?= $isActive(['site/index']) ?>" href="<?= Url::to(['/site/index']) ?>">
            <span class="admin-sidebar__icon">◫</span> Dashboard
        </a>
        <a class="admin-sidebar__link<?= $isActive(['admin/index']) ?>" href="<?= Url::to(['/admin/index']) ?>">
            <span class="admin-sidebar__icon">◆</span> Manage Platform
        </a>

        <div class="admin-sidebar__section">Moderation</div>
        <a class="admin-sidebar__link<?= $isActive(['complaint/index', 'complaint/view']) ?>" href="<?= Url::to(['/complaint/index']) ?>">
            <span class="admin-sidebar__icon">!</span> Complaints
        </a>
        <a class="admin-sidebar__link<?= $isActive(['support/index', 'support/view']) ?>" href="<?= Url::to(['/support/index']) ?>">
            <span class="admin-sidebar__icon">✉</span> Support Inbox
        </a>
        <a class="admin-sidebar__link<?= $isActive(['audit/index']) ?>" href="<?= Url::to(['/audit/index']) ?>">
            <span class="admin-sidebar__icon">◷</span> Audit Log
        </a>

        <div class="admin-sidebar__section">Finance</div>
        <a class="admin-sidebar__link<?= $isActive(['payment/index']) ?>" href="<?= Url::to(['/payment/index']) ?>">
            <span class="admin-sidebar__icon">◈</span> Payments
        </a>
    </nav>

    <div class="admin-sidebar__footer">
        <div class="admin-sidebar__user">
            <div class="admin-sidebar__avatar"><?= Html::encode($initials) ?></div>
            <div>
                <div class="admin-sidebar__user-name"><?= Html::encode($user->getFullName()) ?></div>
                <div class="admin-sidebar__user-role">Administrator</div>
            </div>
        </div>
        <?= Html::beginForm(['/site/logout'], 'post') ?>
            <?= Html::submitButton('Sign out', ['class' => 'admin-sidebar__link w-100 border-0 bg-transparent text-start']) ?>
        <?= Html::endForm() ?>
    </div>
</aside>
<div class="admin-sidebar-overlay" id="admin-sidebar-overlay" aria-hidden="true"></div>
