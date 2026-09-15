<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var string $roleLabel */
/** @var string $loginUrl */

$this->title = 'Welcome to Bargain';
?>
<p>Hello <?= Html::encode($user->getFullName()) ?>,</p>

<p>Welcome to <strong>Bargain</strong>! Your <?= Html::encode($roleLabel) ?> account is ready.</p>

<p>You can sign in anytime:</p>
<p><?= Html::a(Html::encode($loginUrl), $loginUrl) ?></p>

<p>— The Bargain team</p>
