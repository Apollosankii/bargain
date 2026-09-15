<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\SubscriptionPayment $payment */

use common\models\Subscription;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Confirming Payment — Bargain';
$this->context->layout = 'auth';

$planLabel = Subscription::planOptions()[$payment->plan] ?? $payment->plan;
$statusUrl = Url::to(['/subscription/status', 'id' => $payment->subscription_payment_id]);
$chooseUrl = Url::to(['/subscription/choose']);
$statusUrlJs = json_encode($statusUrl, JSON_THROW_ON_ERROR);
$chooseUrlJs = json_encode($chooseUrl, JSON_THROW_ON_ERROR);
$this->registerJs(<<<JS
(function () {
    const statusUrl = {$statusUrlJs};
    const chooseUrl = {$chooseUrlJs};

    async function poll() {
        try {
            const response = await fetch(statusUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok) {
                return;
            }
            const data = await response.json();
            if (data.status === 'COMPLETED' && data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            if (data.status === 'FAILED' || data.status === 'CANCELLED') {
                const message = document.getElementById('payment-status-message');
                if (message) {
                    message.textContent = data.resultDesc || 'Payment was not completed.';
                    message.classList.add('payment-status-error');
                }
                const spinner = document.getElementById('payment-spinner');
                if (spinner) {
                    spinner.style.display = 'none';
                }
                const retry = document.getElementById('payment-retry');
                if (retry) {
                    retry.style.display = 'block';
                }
                return;
            }
        } catch (e) {
            // Keep polling on transient errors.
        }
        window.setTimeout(poll, 3000);
    }

    window.setTimeout(poll, 2000);
})();
JS);
?>
<div class="auth-page">
    <div class="auth-box subscription-box">
        <div style="text-align:center; margin-bottom:1.5rem;">
            <div class="auth-logo">BARGAIN</div>
        </div>

        <h1 class="auth-title" style="text-align:center;">Check Your Phone</h1>
        <p class="auth-subtitle" style="text-align:center;">
            We sent an M-Pesa prompt to <strong><?= Html::encode($payment->phone) ?></strong>
            for <strong><?= Html::encode($planLabel) ?></strong> (KES <?= number_format((float) $payment->amount) ?>).
        </p>

        <div id="payment-spinner" class="payment-waiting-spinner" aria-hidden="true"></div>
        <p id="payment-status-message" class="payment-status-message">
            Enter your M-Pesa PIN on your phone to complete payment.
        </p>

        <div id="payment-retry" style="display:none; margin-top:1.25rem; text-align:center;">
            <?= Html::a('Try another plan', $chooseUrl, ['class' => 'btn btn-primary btn-sm']) ?>
        </div>

        <div style="margin-top:1.25rem; text-align:center;">
            <p style="font-size:0.8rem; color:var(--text-secondary);">
                This page updates automatically once payment is confirmed.
            </p>
        </div>
    </div>
</div>
