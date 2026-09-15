<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Payment $payment */

use common\models\Auction;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Confirming Payment — Bargain';

$auction = $payment->auction;
$statusUrl = Url::to(['/payment/status', 'id' => $payment->payment_id]);
$retryUrl = Url::to(['/payment/create', 'auction_id' => $payment->auction_id]);
$statusUrlJs = json_encode($statusUrl, JSON_THROW_ON_ERROR);
$retryUrlJs = json_encode($retryUrl, JSON_THROW_ON_ERROR);
$this->registerJs(<<<JS
(function () {
    const statusUrl = {$statusUrlJs};
    const retryUrl = {$retryUrlJs};

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
            if (data.status === 'FAILED' || data.status === 'REFUNDED') {
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
<div class="page" style="max-width:560px;">
    <div class="card" style="text-align:center;">
        <h1 class="page-title" style="margin:0 0 0.5rem;">Check Your Phone</h1>
        <p style="color:var(--text-secondary); font-size:0.9rem;">
            We sent an M-Pesa prompt to <strong><?= Html::encode($payment->phone) ?></strong>
            for <strong><?= Html::encode($auction?->title ?? 'your auction win') ?></strong>
            (<?= Auction::formatKes($payment->amount) ?>).
        </p>

        <div id="payment-spinner" class="payment-waiting-spinner" aria-hidden="true"></div>
        <p id="payment-status-message" class="payment-status-message">
            Enter your M-Pesa PIN on your phone to complete payment.
        </p>

        <div id="payment-retry" style="display:none; margin-top:1.25rem;">
            <a href="<?= Html::encode($retryUrl) ?>" class="btn btn-primary btn-sm">Try again</a>
        </div>

        <p style="margin-top:1.25rem; font-size:0.8rem; color:var(--text-secondary);">
            This page updates automatically once payment is confirmed.
        </p>
    </div>
</div>
