<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Bid[] $bids */
/** @var common\models\Bid[] $watchlist */
/** @var string $tab */

use common\models\Auction;
use common\models\Payment;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'My Bids — Bargain';
$items = $tab === 'watchlist' ? $watchlist : $bids;
/** @var \common\models\User $user */
$user = Yii::$app->user->identity;

$paidAuctionIds = [];
if (!empty($items)) {
    $auctionIds = array_values(array_unique(array_map(static fn ($b) => (int) $b->auction_id, $items)));
    if ($auctionIds) {
        $rows = Payment::find()
            ->select(['auction_id'])
            ->where(['user_id' => $user->user_id, 'status' => Payment::STATUS_COMPLETED])
            ->andWhere(['auction_id' => $auctionIds])
            ->asArray()
            ->all();
        $paidAuctionIds = array_fill_keys(array_map(static fn ($r) => (int) $r['auction_id'], $rows), true);
    }
}
?>
<div class="layout-content__inner portal-page">
    <header class="portal-page__header">
        <div>
            <h1 class="portal-page__title">My Bids</h1>
            <p class="portal-page__subtitle">Track auctions you've bid on and items you're currently winning.</p>
        </div>
        <div class="portal-page__actions">
            <a class="btn <?= $tab === 'bids' ? 'btn-primary' : 'btn-outline' ?> btn-sm" href="<?= Url::to(['/bid/my-bids']) ?>">All bids</a>
            <a class="btn <?= $tab === 'watchlist' ? 'btn-primary' : 'btn-outline' ?> btn-sm" href="<?= Url::to(['/bid/my-bids', 'tab' => 'watchlist']) ?>">Watchlist</a>
        </div>
    </header>

    <section class="portal-section">
        <?php if (empty($items)): ?>
            <div class="empty-state">
                <p class="empty-state-text">
                    <?= $tab === 'watchlist' ? 'You are not winning any auctions yet.' : 'You have not placed any bids yet.' ?>
                </p>
                <a href="<?= Url::to(['/dashboard/bidder']) ?>" class="btn btn-outline btn-sm" style="margin-top:1rem;">Browse auctions</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Auction</th>
                            <th>Your bid</th>
                            <th>Current</th>
                            <th>Status</th>
                            <th>Ends</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $bid): ?>
                            <tr>
                                <td>
                                    <a href="<?= Url::to(['/auction/view', 'id' => $bid->auction_id]) ?>">
                                        <?= Html::encode($bid->auction_title) ?>
                                    </a>
                                    <div style="font-size:0.75rem;color:var(--portal-text-muted);">
                                        <?= Html::encode($bid->category_name) ?>
                                    </div>
                                </td>
                                <td><?= Auction::formatKes($bid->bid_amount) ?></td>
                                <td><?= Auction::formatKes($bid->current_bid) ?></td>
                                <td>
                                    <?php
                                    $st = $bid->bid_status ?? $bid->status;
                                    $cls = match ($st) {
                                        'WINNING', 'WON' => 'badge-success',
                                        'OUTBID' => 'badge-warning',
                                        default => 'badge-muted',
                                    };
                                    ?>
                                    <span class="badge <?= $cls ?>"><?= Html::encode($st) ?></span>
                                </td>
                                <td style="color:var(--portal-text-secondary);font-size:0.85rem;">
                                    <?= Html::encode(Auction::timeRemaining($bid->end_time)) ?>
                                </td>
                                <td>
                                    <?php
                                    $st = $bid->bid_status ?? $bid->status;
                                    $needsPay = $st === 'WON' && empty($paidAuctionIds[(int) $bid->auction_id]);
                                    ?>
                                    <?php if ($needsPay): ?>
                                        <a class="btn btn-primary btn-sm" href="<?= Url::to(['/payment/create', 'auction_id' => $bid->auction_id]) ?>">
                                            Pay with M-Pesa
                                        </a>
                                    <?php elseif ($st === 'WON'): ?>
                                        <span class="badge badge-success">PAID</span>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
