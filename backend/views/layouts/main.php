<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use common\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\helpers\Html;

$this->render('_head');
$pageTitle = Html::encode($this->title);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100" data-bs-theme="light">
<head>
    <?php $this->head() ?>
    <title><?= $pageTitle ?> — Bargain Admin</title>
</head>
<body class="admin-app">
<?php $this->beginBody() ?>

<div class="admin-layout">
    <?php if (!Yii::$app->user->isGuest): ?>
        <?= $this->render('_sidebar') ?>
    <?php endif; ?>

    <div class="admin-shell">
        <?php if (!Yii::$app->user->isGuest): ?>
            <header class="admin-topbar">
                <div class="admin-topbar__left">
                    <button type="button" class="admin-topbar__menu-btn" id="admin-menu-btn" aria-label="Open menu">☰</button>
                    <h2 class="admin-topbar__title"><?= $pageTitle ?></h2>
                </div>
                <div class="admin-topbar__actions">
                    <?= Html::button('&#127769;', [
                        'id' => 'theme-toggle',
                        'class' => 'admin-topbar__theme',
                        'aria-label' => 'Switch theme',
                        'type' => 'button',
                    ]) ?>
                </div>
            </header>
        <?php else: ?>
            <?= $this->render('_header') ?>
        <?php endif; ?>

        <main id="main" class="admin-content" role="main">
            <?php if (!empty($this->params['breadcrumbs'])): ?>
                <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
            <?php endif ?>
            <?= Alert::widget() ?>
            <?= $content ?>
        </main>

        <?php if (!Yii::$app->user->isGuest): ?>
            <footer class="admin-footer">
                &copy; <?= Html::encode(Yii::$app->name) ?> <?= date('Y') ?> · Admin Console
            </footer>
        <?php else: ?>
            <?= $this->render('_footer') ?>
        <?php endif; ?>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
