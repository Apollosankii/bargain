<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\Auction $auction */
/** @var string $amountLabel */
/** @var string $auctionUrl */

$this->title = 'You won an auction';
?>
<p>Hello <?= Html::encode($user->getFullName()) ?>,</p>

<p>
    Congratulations! You won <strong><?= Html::encode($auction->title) ?></strong>
    for <strong><?= Html::encode($amountLabel) ?></strong>.
</p>

<p>Open the auction to complete payment via M-Pesa.</p>

<p><?= Html::a('Complete payment', $auctionUrl) ?></p>
