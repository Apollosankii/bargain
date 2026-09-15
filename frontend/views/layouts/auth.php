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

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-bs-theme="dark" data-force-dark="1" data-theme-scope="public">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <script>
        document.documentElement.setAttribute('data-bs-theme', 'dark');
        document.documentElement.setAttribute('data-force-dark', '1');
    </script>
    <?php $this->head() ?>
</head>
<body class="auth-layout landing-body">
<?php $this->beginBody() ?>
<main>
    <?php if ($flashes): ?>
        <div style="max-width:420px;margin:1rem auto 0;padding:0 1rem;">
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
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
