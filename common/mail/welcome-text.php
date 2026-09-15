<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var string $roleLabel */
/** @var string $loginUrl */
?>
Hello <?= $user->getFullName() ?>,

Welcome to Bargain! Your <?= $roleLabel ?> account is ready.

Sign in: <?= $loginUrl ?>

— The Bargain team
