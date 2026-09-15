<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use frontend\assets\AppAsset;
use yii\helpers\Html;

AppAsset::register($this);

$flashes = Yii::$app->session->getAllFlashes(true);
$typeClass = [
    'success' => 'alert-success',
    'error' => 'alert-danger',
    'danger' => 'alert-danger',
    'info' => 'alert-info',
    'warning' => 'alert-info',
];
$forceDark = !empty($this->params['forceDarkTheme']);
$landingLayout = !empty($this->params['landingLayout']);
$portalLayout = !$landingLayout && !Yii::$app->user->isGuest;

if ($portalLayout) {
    $this->render('_register_portal_assets');
}
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html
    lang="<?= Yii::$app->language ?>"
    class="h-100"
    data-bs-theme="dark"
    data-theme-scope="<?= $forceDark || $landingLayout ? 'public' : ($portalLayout ? 'portal' : 'app') ?>"
    <?php if ($forceDark || $landingLayout): ?>data-force-dark="1"<?php endif; ?>
>
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <script>
        (function () {
            var html = document.documentElement;
            if (html.getAttribute('data-force-dark') === '1') {
                html.setAttribute('data-bs-theme', 'dark');
                return;
            }
            var stored = localStorage.getItem('theme');
            html.setAttribute('data-bs-theme', stored === 'light' ? 'light' : 'dark');
        })();
    </script>
    <?php $this->head() ?>
</head>
<body<?= $portalLayout ? ' class="portal"' : '' ?><?= $landingLayout ? ' class="landing-body"' : '' ?>>
<?php $this->beginBody() ?>

<?= $landingLayout
    ? $this->render('_header_landing')
    : $this->render('_header', ['portalLayout' => $portalLayout]) ?>

<main class="layout-content<?= $landingLayout ? ' layout-content--landing' : '' ?>" role="main">
    <?php if ($flashes): ?>
        <div class="<?= $portalLayout ? 'portal-flashes' : '' ?>"<?= !$portalLayout ? ' style="max-width:1100px;margin:1rem auto 0;padding:0 1.5rem;"' : '' ?>>
            <?php foreach ($flashes as $type => $flash): ?>
                <?php if (!isset($typeClass[$type])) {
                    continue;
                } ?>
                <?php foreach ((array) $flash as $message): ?>
                    <div class="alert <?= $typeClass[$type] ?>"><?= Html::encode($message) ?></div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>

<?= $landingLayout
    ? $this->render('_footer_landing')
    : $this->render('_footer') ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
