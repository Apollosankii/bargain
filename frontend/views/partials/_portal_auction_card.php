<?php

declare(strict_types=1);

/** @var common\models\Auction $auction */

use common\models\Auction;
use yii\helpers\Html;
use yii\helpers\Url;

$endsAt = strtotime((string) $auction->end_time);
$isEndingSoon = $endsAt !== false && $endsAt <= time() + 86400;
$statusClass = $isEndingSoon ? 'badge-warning' : 'badge-live';
$statusLabel = $isEndingSoon ? 'Ending Soon' : 'Active';
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
            <span class="portal-lot__countdown"><?= Html::encode(Auction::timeRemaining((string) $auction->end_time)) ?></span>
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
            <a class="btn btn-outline btn-sm" href="<?= Url::to(['/auction/view', 'id' => $auction->auction_id]) ?>">
                View Auction
            </a>
            <?= Html::beginForm(['/auction/end', 'id' => $auction->auction_id], 'post') ?>
                <?= Html::submitButton('End', [
                    'class' => 'btn btn-danger btn-sm',
                    'data-confirm' => 'End this auction now?',
                ]) ?>
            <?= Html::endForm() ?>
        </div>
    </div>
</article>
