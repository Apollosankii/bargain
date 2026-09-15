<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\Auction $auction */
/** @var string $amountLabel */
/** @var string $auctionUrl */

$this->title = 'You have been outbid';
?>
<p>Hello <?= Html::encode($user->getFullName()) ?>,</p>

<p>
    You have been outbid on <strong><?= Html::encode($auction->title) ?></strong>.
    The new highest bid is <strong><?= Html::encode($amountLabel) ?></strong>.
</p>

<p>Place a higher bid to stay in the running.</p>

<p><?= Html::a('Return to auction', $auctionUrl) ?></p>
