<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\Auction $auction */
/** @var common\models\Payment $payment */
/** @var string $amountLabel */
/** @var string $auctionUrl */

$this->title = 'Payment received';
?>
<p>Hello <?= Html::encode($user->getFullName()) ?>,</p>

<p>
    A winning bidder paid <strong><?= Html::encode($amountLabel) ?></strong>
    for your auction <strong><?= Html::encode($auction->title) ?></strong>.
</p>

<?php if ($payment->mpesa_receipt): ?>
    <p>M-Pesa receipt: <strong><?= Html::encode((string) $payment->mpesa_receipt) ?></strong></p>
<?php endif; ?>

<p><?= Html::a('View auction', $auctionUrl) ?></p>
