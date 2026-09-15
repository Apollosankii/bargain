<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\Auction $auction */
/** @var string $amountLabel */
/** @var string $auctionUrl */
?>
Hello <?= $user->getFullName() ?>,

You have been outbid on "<?= $auction->title ?>".
The new highest bid is <?= $amountLabel ?>.

Place a higher bid to stay in the running:
<?= $auctionUrl ?>
