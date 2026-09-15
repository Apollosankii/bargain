<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\Auction $auction */
/** @var string|null $winningAmountLabel */
/** @var string $auctionUrl */
?>
Hello <?= $user->getFullName() ?>,

<?php if ($winningAmountLabel !== null): ?>
Your auction "<?= $auction->title ?>" has ended.
Winning bid: <?= $winningAmountLabel ?>.

You will be notified again when the winner completes payment.
<?php else: ?>
Your auction "<?= $auction->title ?>" ended with no bids.
You can extend it from the auction page.
<?php endif; ?>

View auction: <?= $auctionUrl ?>
