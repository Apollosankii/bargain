<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\Auction $auction */
/** @var common\models\Payment $payment */
/** @var string $amountLabel */
/** @var string $auctionUrl */
?>
Hello <?= $user->getFullName() ?>,

A winning bidder paid <?= $amountLabel ?> for your auction "<?= $auction->title ?>".
<?php if ($payment->mpesa_receipt): ?>

M-Pesa receipt: <?= $payment->mpesa_receipt ?>
<?php endif; ?>

View auction: <?= $auctionUrl ?>
