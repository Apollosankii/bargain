<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\LoginForm $model */
/** @var common\models\Auction|null $heroAuction */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->render('_register_public_assets');

$this->title = 'Sign In — Bargain';
?>
<div class="marketing marketing-auth">
    <div class="marketing-auth__grid">
        <aside class="marketing-auth__brand" aria-label="Bargain brand">
            <a class="marketing-auth__brand-logo" href="<?= Url::to(['/site/index']) ?>">BARGAIN</a>

            <p class="marketing-auth__brand-tagline">The smart way to bid.</p>

            <div class="marketing-auth__brand-visual<?= ($heroAuction === null || !$heroAuction->image_url) ? ' marketing-auth__brand-visual--empty' : '' ?>">
                <?php if ($heroAuction !== null && $heroAuction->image_url): ?>
                    <img
                        src="<?= Html::encode($heroAuction->image_url) ?>"
                        alt="<?= Html::encode($heroAuction->title) ?>"
                    />
                <?php else: ?>
                    <span>Live auctions</span>
                <?php endif; ?>
            </div>
        </aside>

        <div class="marketing-auth__panel">
            <div class="marketing-auth__form-wrap">
                <h1 class="marketing-auth__title">Welcome back.</h1>
                <p class="marketing-auth__subtitle">
                    Sign in to continue bidding on great deals.
                </p>

                <?php $form = ActiveForm::begin([
                    'id' => 'login-form',
                    'enableClientValidation' => true,
                    'options' => ['class' => 'marketing-form'],
                    'fieldConfig' => [
                        'template' => "{label}\n{input}\n{error}",
                        'labelOptions' => ['class' => 'marketing-form__label'],
                        'inputOptions' => ['class' => 'marketing-form__input'],
                        'errorOptions' => ['class' => 'marketing-form__error help-block'],
                        'options' => ['class' => 'form-group'],
                    ],
                ]); ?>

                <?= $form->field($model, 'email')->textInput([
                    'type' => 'email',
                    'autofocus' => true,
                    'placeholder' => 'you@example.com',
                ]) ?>

                <?= $form->field($model, 'password')->passwordInput([
                    'placeholder' => 'Your password',
                ]) ?>

                <div class="marketing-form__remember">
                    <?= $form->field($model, 'rememberMe', [
                        'template' => "<div class=\"marketing-form__remember-row\">{input}{label}</div>\n{error}",
                        'options' => ['class' => 'form-group', 'style' => 'margin-bottom:0'],
                    ])->checkbox(['label' => 'Remember me']) ?>
                </div>

                <div class="marketing-form__actions">
                    <?= Html::submitButton('Sign In', [
                        'class' => 'landing-btn landing-btn--primary',
                        'name' => 'login-button',
                    ]) ?>
                    <a href="<?= Url::to(['/site/signup']) ?>" class="marketing-form__secondary">
                        Create an account →
                    </a>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
