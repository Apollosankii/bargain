<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\Auction $auction */
/** @var string|null $winningAmountLabel */
/** @var string $auctionUrl */

$this->title = 'Auction ended';
?>
<p>Hello <?= Html::encode($user->getFullName()) ?>,</p>

<?php if ($winningAmountLabel !== null): ?>
    <p>
        Your auction <strong><?= Html::encode($auction->title) ?></strong> has ended.
        Winning bid: <strong><?= Html::encode($winningAmountLabel) ?></strong>.
    </p>
    <p>You will be notified again when the winner completes payment.</p>
<?php else: ?>
    <p>
        Your auction <strong><?= Html::encode($auction->title) ?></strong> ended with no bids.
        You can extend it from the auction page.
    </p>
<?php endif; ?>

<p><?= Html::a('View auction', $auctionUrl) ?></p>
