<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\Auction $auction */
/** @var string $auctionUrl */
?>
Hello <?= $user->getFullName() ?>,

Your auction "<?= $auction->title ?>" has been created successfully.

Starting bid: <?= \common\models\Auction::formatKes($auction->starting_bid) ?>
Starts: <?= date('M j, Y H:i', strtotime((string) $auction->start_time)) ?>
Ends: <?= date('M j, Y H:i', strtotime((string) $auction->end_time)) ?>

View auction: <?= $auctionUrl ?>
