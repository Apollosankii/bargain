<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\Auction $auction */
/** @var common\models\User $bidder */
/** @var string $amountLabel */
/** @var string $auctionUrl */

$this->title = 'New bid received';
?>
<p>Hello <?= Html::encode($user->getFullName()) ?>,</p>

<p>
    <strong><?= Html::encode($bidder->getFullName()) ?></strong> placed a bid of
    <strong><?= Html::encode($amountLabel) ?></strong> on your auction
    <strong><?= Html::encode($auction->title) ?></strong>.
</p>

<p><?= Html::a('View auction', $auctionUrl) ?></p>
