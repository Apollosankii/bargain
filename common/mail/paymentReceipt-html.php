<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\Auction $auction */
/** @var common\models\Payment $payment */
/** @var string $amountLabel */
/** @var string $paymentUrl */

$this->title = 'Payment receipt';
?>
<p>Hello <?= Html::encode($user->getFullName()) ?>,</p>

<p>
    Thank you. Your payment of <strong><?= Html::encode($amountLabel) ?></strong>
    for <strong><?= Html::encode($auction->title) ?></strong> was successful.
</p>

<ul>
    <?php if ($payment->mpesa_receipt): ?>
        <li>M-Pesa receipt: <strong><?= Html::encode((string) $payment->mpesa_receipt) ?></strong></li>
    <?php endif; ?>
    <li>Amount: <?= Html::encode($amountLabel) ?></li>
    <li>Phone: <?= Html::encode((string) ($payment->phone ?: '—')) ?></li>
</ul>

<p><?= Html::a('View receipt', $paymentUrl) ?></p>
