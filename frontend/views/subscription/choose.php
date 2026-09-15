<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var bool $canStartTrial */
/** @var array<string, array{title: string, subtitle: string, detail: string, cta: string}> $plans */
/** @var array<string, int> $prices */

use common\models\Subscription;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Choose a Plan — Bargain';
$this->context->layout = 'auth';
?>
<div class="auth-page">
    <div class="auth-box subscription-box">
        <div style="text-align:center; margin-bottom:1.5rem;">
            <div class="auth-logo">BARGAIN</div>
            <p style="font-size:0.82rem; color:var(--text-secondary);">
                Auctioneer subscription
            </p>
        </div>

        <h1 class="auth-title" style="text-align:center;">Choose Your Plan</h1>
        <p class="auth-subtitle" style="text-align:center;">
            Start with a free trial or pay instantly via M-Pesa STK Push.
        </p>

        <div class="plan-grid">
            <?php foreach ($plans as $planKey => $plan): ?>
                <?php
                $isTrial = $planKey === Subscription::PLAN_FREE_TRIAL;
                $disabled = $isTrial && !$canStartTrial;
                $price = $prices[$planKey] ?? null;
                ?>
                <div class="plan-card<?= $isTrial ? ' plan-card-trial' : '' ?><?= $disabled ? ' plan-card-disabled' : '' ?>">
                    <div class="plan-card-title"><?= Html::encode($plan['title']) ?></div>
                    <div class="plan-card-subtitle">
                        <?php if ($isTrial): ?>
                            <?= Html::encode($plan['subtitle']) ?>
                        <?php else: ?>
                            KES <?= number_format((int) $price) ?> · <?= Html::encode($plan['subtitle']) ?>
                        <?php endif; ?>
                    </div>
                    <p class="plan-card-detail"><?= Html::encode($plan['detail']) ?></p>

                    <?php if ($disabled): ?>
                        <button type="button" class="btn btn-outline btn-full" disabled>
                            Free trial already used
                        </button>
                    <?php elseif ($isTrial): ?>
                        <?= Html::beginForm(Url::to(['/subscription/activate']), 'post') ?>
                            <?= Html::hiddenInput('plan', $planKey) ?>
                            <?= Html::submitButton(Html::encode($plan['cta']), [
                                'class' => 'btn btn-outline btn-full',
                            ]) ?>
                        <?= Html::endForm() ?>
                    <?php else: ?>
                        <?= Html::a(
                            Html::encode($plan['cta']),
                            ['/subscription/pay', 'plan' => $planKey],
                            ['class' => 'btn btn-primary btn-full'],
                        ) ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:1.25rem; text-align:center;">
            <?= Html::beginForm(['/site/logout'], 'post') ?>
                <?= Html::submitButton('Logout', ['class' => 'btn btn-outline btn-sm']) ?>
            <?= Html::endForm() ?>
        </div>
    </div>
</div>
