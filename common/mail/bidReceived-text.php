<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\Auction $auction */
/** @var common\models\User $bidder */
/** @var string $amountLabel */
/** @var string $auctionUrl */
?>
Hello <?= $user->getFullName() ?>,

<?= $bidder->getFullName() ?> placed a bid of <?= $amountLabel ?> on your auction "<?= $auction->title ?>".

View auction: <?= $auctionUrl ?>
