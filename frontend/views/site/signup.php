<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var frontend\models\SignupForm $model */

use common\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'Sign Up — Bargain';
$this->context->layout = 'auth';
?>
<div class="auth-page">
    <div class="auth-box" style="max-width:480px;">
        <div style="text-align:center; margin-bottom:1.5rem;">
            <div class="auth-logo">BARGAIN</div>
            <p style="font-size:0.82rem; color:var(--text-secondary);">
                Smart bargaining system
            </p>
        </div>

        <h1 class="auth-title" style="text-align:center;">Sign Up</h1>

        <?php $form = ActiveForm::begin([
            'id' => 'form-signup',
            'enableClientValidation' => true,
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'options' => ['class' => 'form-group'],
            ],
        ]); ?>

        <?= $form->field($model, 'role')->dropDownList([
            '' => 'Select Role',
            User::ROLE_BIDDER => 'Bidder — I want to bid on auctions',
            User::ROLE_AUCTIONEER => 'Auctioneer — I want to sell items',
        ]) ?>

        <div class="form-row">
            <?= $form->field($model, 'first_name')->textInput(['placeholder' => 'First name']) ?>
            <?= $form->field($model, 'last_name')->textInput(['placeholder' => 'Last name']) ?>
        </div>

        <?= $form->field($model, 'email')->textInput([
            'type' => 'email',
            'placeholder' => 'Enter your email',
        ]) ?>

        <?= $form->field($model, 'phone')->textInput([
            'type' => 'tel',
            'placeholder' => '+254712345678',
        ]) ?>

        <?= $form->field($model, 'password')->passwordInput([
            'placeholder' => 'Minimum 6 characters',
        ]) ?>

        <?= $form->field($model, 'password_repeat')->passwordInput([
            'placeholder' => 'Repeat your password',
        ]) ?>

        <?= $form->field($model, 'agreeTerms', [
            'template' => "{input}\n{error}",
            'options' => ['class' => 'form-group agree-terms-field'],
        ])->checkbox([
            'label' => 'I agree to the '
                . Html::a('Terms & Conditions', Url::to(['/site/terms']), [
                    'target' => '_blank',
                    'rel' => 'noopener',
                ]),
            'class' => 'agree-terms-checkbox',
            'labelOptions' => ['class' => 'agree-terms-label'],
        ]) ?>

        <div class="form-group">
            <?= Html::submitButton('Create Account', [
                'class' => 'btn btn-primary btn-full',
                'name' => 'signup-button',
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>

        <div style="margin-top:1rem; text-align:center;">
            <span style="color:var(--text-secondary); font-size:0.85rem;">
                Already have an account?
                <a href="<?= Url::to(['/site/login']) ?>">Sign In</a>
            </span>
        </div>
    </div>
</div>
