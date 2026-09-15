<?php

declare(strict_types=1);

/** @var common\models\Auction $auction */
/** @var bool $isLeading */
/** @var bool $isScheduled */

use common\models\Auction;
use yii\helpers\Html;
use yii\helpers\Url;

$isLeading = $isLeading ?? false;
$isScheduled = $isScheduled ?? $auction->isScheduled();
$endsAt = strtotime((string) $auction->end_time);
$isEndingSoon = !$isScheduled && $endsAt !== false && $endsAt <= time() + 86400;
$statusClass = $isScheduled ? 'badge-muted' : ($isEndingSoon ? 'badge-warning' : 'badge-live');
$statusLabel = $isScheduled ? 'Upcoming' : ($isEndingSoon ? 'Ending Soon' : 'Live');
?>
<article class="portal-lot">
    <div class="portal-lot__media">
        <?php if ($auction->image_url): ?>
            <img src="<?= Html::encode($auction->image_url) ?>" alt="<?= Html::encode($auction->title) ?>" loading="lazy" />
        <?php else: ?>
            <div class="portal-lot__media-placeholder" aria-hidden="true"></div>
        <?php endif; ?>
        <div class="portal-lot__badges">
            <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
            <span class="portal-lot__countdown">
                <?php if ($isScheduled): ?>
                    Starts <?= Html::encode(date('M j, g:i A', strtotime((string) $auction->start_time))) ?>
                <?php else: ?>
                    <?= Html::encode(Auction::timeRemaining((string) $auction->end_time)) ?>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <div class="portal-lot__body">
        <h3 class="portal-lot__title">
            <a href="<?= Url::to(['/auction/view', 'id' => $auction->auction_id]) ?>">
                <?= Html::encode($auction->title) ?>
            </a>
        </h3>

        <div class="portal-lot__stats">
            <div>
                <div class="portal-lot__stat-label">Current bid</div>
                <div class="portal-lot__stat-value portal-lot__stat-value--price">
                    <?= Auction::formatKes($auction->current_bid) ?>
                </div>
            </div>
            <div>
                <div class="portal-lot__stat-label">Bids</div>
                <div class="portal-lot__stat-value"><?= (int) $auction->total_bids ?></div>
            </div>
        </div>

        <div class="portal-lot__actions">
            <?php if ($isLeading): ?>
                <a class="btn btn-outline btn-sm" href="<?= Url::to(['/auction/view', 'id' => $auction->auction_id]) ?>">You're leading</a>
            <?php elseif ($isScheduled): ?>
                <a class="btn btn-outline btn-sm" href="<?= Url::to(['/auction/view', 'id' => $auction->auction_id]) ?>">View auction</a>
            <?php else: ?>
                <a class="btn btn-primary btn-sm" href="<?= Url::to(['/auction/view', 'id' => $auction->auction_id]) ?>">Place bid</a>
            <?php endif; ?>
        </div>
    </div>
</article>

