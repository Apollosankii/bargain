<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Payment $payment */

use common\models\Auction;
use common\models\Payment;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Payment — Bargain';
$auction = $payment->auction;
?>
<div class="page" style="max-width:720px;">
    <div class="flex-between mb-2">
        <h1 class="page-title" style="margin:0;">Payment</h1>
        <a href="<?= Url::to(['/payment/my-payments']) ?>" class="btn btn-outline btn-sm">My payments</a>
    </div>

    <div class="card">
        <div class="flex-between" style="margin-bottom:0.6rem;">
            <div>
                <div style="font-weight:800;"><?= Html::encode($auction?->title ?? ('Auction #' . $payment->auction_id)) ?></div>
                <div style="color:var(--text-secondary);font-size:0.85rem;">
                    Amount: <strong><?= Auction::formatKes($payment->amount) ?></strong>
                </div>
            </div>
            <span class="badge <?= Payment::statusBadgeClass($payment->status) ?>">
                <?= Html::encode($payment->status) ?>
            </span>
        </div>

        <div style="color:var(--text-secondary);font-size:0.9rem;">
            Method: <strong><?= Html::encode(Payment::methodOptions()[$payment->method] ?? $payment->method) ?></strong>
        </div>
        <?php if ($payment->mpesa_receipt): ?>
            <div style="color:var(--text-secondary);font-size:0.9rem;margin-top:0.35rem;">
                M-Pesa receipt: <strong><?= Html::encode($payment->mpesa_receipt) ?></strong>
            </div>
        <?php endif; ?>
        <?php if ($payment->phone): ?>
            <div style="color:var(--text-secondary);font-size:0.9rem;margin-top:0.35rem;">
                Phone: <strong><?= Html::encode($payment->phone) ?></strong>
            </div>
        <?php endif; ?>
        <div style="color:var(--text-muted);font-size:0.8rem;margin-top:0.5rem;">
            Created: <?= Html::encode(date('M j, Y H:i', strtotime((string) $payment->created_at))) ?>
        </div>

        <?php if ($auction): ?>
            <div style="margin-top:1rem;">
                <a class="btn btn-outline btn-sm" href="<?= Url::to(['/auction/view', 'id' => $auction->auction_id]) ?>">
                    View auction
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

