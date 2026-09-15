<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\Auction $auction */
/** @var string $auctionUrl */

$this->title = 'Auction created';
?>
<p>Hello <?= Html::encode($user->getFullName()) ?>,</p>

<p>Your auction <strong><?= Html::encode($auction->title) ?></strong> has been created successfully.</p>

<ul>
    <li>Starting bid: <?= Html::encode(\common\models\Auction::formatKes($auction->starting_bid)) ?></li>
    <li>Starts: <?= Html::encode(date('M j, Y H:i', strtotime((string) $auction->start_time))) ?></li>
    <li>Ends: <?= Html::encode(date('M j, Y H:i', strtotime((string) $auction->end_time))) ?></li>
</ul>

<p><?= Html::a('View auction', $auctionUrl) ?></p>
