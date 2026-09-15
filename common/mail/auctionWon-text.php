<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\Auction $auction */
/** @var string $amountLabel */
/** @var string $auctionUrl */
?>
Hello <?= $user->getFullName() ?>,

Congratulations! You won "<?= $auction->title ?>" for <?= $amountLabel ?>.

Open the auction to complete payment via M-Pesa:
<?= $auctionUrl ?>
