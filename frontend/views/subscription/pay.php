<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $plan */
/** @var string $planLabel */
/** @var int $amount */
/** @var string $phone */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Pay with M-Pesa — Bargain';
$this->context->layout = 'auth';
?>
<div class="auth-page">
    <div class="auth-box subscription-box">
        <div style="text-align:center; margin-bottom:1.5rem;">
            <div class="auth-logo">BARGAIN</div>
            <p style="font-size:0.82rem; color:var(--text-secondary);">
                M-Pesa subscription payment
            </p>
        </div>

        <h1 class="auth-title" style="text-align:center;">Pay with M-Pesa</h1>
        <p class="auth-subtitle" style="text-align:center;">
            <?= Html::encode($planLabel) ?> — <strong>KES <?= number_format($amount) ?></strong>
        </p>

        <?= Html::beginForm(['/subscription/pay', 'plan' => $plan], 'post', ['class' => 'auth-form']) ?>
            <div class="form-group">
                <label class="form-label" for="subscription-phone">M-Pesa phone number</label>
                <?= Html::textInput('phone', $phone, [
                    'id' => 'subscription-phone',
                    'class' => 'form-control',
                    'placeholder' => '0712345678',
                    'required' => true,
                    'autocomplete' => 'tel',
                ]) ?>
                <p class="form-hint">Use the Safaricom number that will receive the STK prompt.</p>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Send STK Push — KES <?= number_format($amount) ?>
            </button>
        <?= Html::endForm() ?>

        <div style="margin-top:1.25rem; text-align:center;">
            <?= Html::a('← Back to plans', ['/subscription/choose'], ['class' => 'btn btn-outline btn-sm']) ?>
        </div>
    </div>
</div>
