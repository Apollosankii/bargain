<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Auction $auction */
/** @var int $amount */
/** @var string $phone */

use common\models\Auction;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Pay with M-Pesa — Bargain';
?>
<div class="page" style="max-width:560px;">
    <div class="flex-between mb-2">
        <h1 class="page-title" style="margin:0;">Pay for Auction</h1>
        <a href="<?= Url::to(['/auction/view', 'id' => $auction->auction_id]) ?>" class="btn btn-outline btn-sm">Back</a>
    </div>

    <div class="card">
        <div style="margin-bottom:1rem;">
            <div style="font-weight:700;"><?= Html::encode($auction->title) ?></div>
            <div style="color:var(--text-secondary);font-size:0.85rem;">
                Amount due: <strong><?= Auction::formatKes($amount) ?></strong>
            </div>
        </div>

        <?= Html::beginForm(['/payment/create', 'auction_id' => $auction->auction_id], 'post', ['class' => 'auth-form']) ?>
            <div class="form-group">
                <label class="form-label" for="payment-phone">M-Pesa phone number</label>
                <?= Html::textInput('phone', $phone, [
                    'id' => 'payment-phone',
                    'class' => 'form-control',
                    'placeholder' => '0712345678',
                    'required' => true,
                    'autocomplete' => 'tel',
                ]) ?>
                <p class="form-hint">Use the Safaricom number that will receive the STK prompt.</p>
            </div>

            <div style="display:flex; gap:0.8rem; justify-content:flex-end;">
                <a href="<?= Url::to(['/auction/view', 'id' => $auction->auction_id]) ?>" class="btn btn-outline">Cancel</a>
                <?= Html::submitButton('Send STK Push — ' . Auction::formatKes($amount), ['class' => 'btn btn-primary']) ?>
            </div>
        <?= Html::endForm() ?>
    </div>
</div>
