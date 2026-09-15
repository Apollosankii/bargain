<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\Auction $auction */
/** @var common\models\Payment $payment */
/** @var string $amountLabel */
/** @var string $paymentUrl */
?>
Hello <?= $user->getFullName() ?>,

Thank you. Your payment of <?= $amountLabel ?> for "<?= $auction->title ?>" was successful.
<?php if ($payment->mpesa_receipt): ?>

M-Pesa receipt: <?= $payment->mpesa_receipt ?>
<?php endif; ?>

Amount: <?= $amountLabel ?>
Phone: <?= $payment->phone ?: '—' ?>

View receipt: <?= $paymentUrl ?>
