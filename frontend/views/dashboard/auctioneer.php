<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Auction[] $active */
/** @var common\models\Auction[] $timedOut */
/** @var common\models\Auction[] $closed */
/** @var common\models\Auction[] $cancelled */
/** @var int $statActive */
/** @var int $statBids */
/** @var float $statEarnings */
/** @var int $unread */

use common\models\Auction;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Auctioneer Dashboard — Bargain';
$user = Yii::$app->user->identity;
$firstName = Html::encode($user->first_name ?? 'there');
?>
<div class="layout-content__inner portal-page">
    <header class="portal-page__header">
        <div>
            <h1 class="portal-page__greeting">Welcome back, <?= $firstName ?></h1>
            <p class="portal-page__subtitle">Here's what's happening with your auctions today.</p>
        </div>
        <div class="portal-page__actions">
            <a href="<?= Url::to(['/dashboard/auctioneer-stats']) ?>" class="btn btn-outline btn-sm">Full Stats</a>
            <a href="<?= Url::to(['/auction/create']) ?>" class="btn btn-primary btn-sm">+ Create Auction</a>
        </div>
    </header>

    <section class="portal-section" aria-labelledby="quick-stats-title">
        <div class="portal-section__head">
            <h2 id="quick-stats-title" class="portal-section__title">Quick Stats</h2>
        </div>
        <div class="portal-stat-grid">
            <?= $this->render('/partials/_portal_stat', [
                'label' => 'Active Auctions',
                'value' => $statActive,
                'icon' => 'brand',
                'iconChar' => '◆',
            ]) ?>
            <?= $this->render('/partials/_portal_stat', [
                'label' => 'Pending Bids',
                'value' => $statBids,
                'icon' => 'warning',
                'iconChar' => '◷',
            ]) ?>
            <?= $this->render('/partials/_portal_stat', [
                'label' => 'Total Earnings',
                'value' => Auction::formatKes($statEarnings),
                'icon' => 'success',
                'iconChar' => '◈',
            ]) ?>
        </div>
    </section>

    <section class="portal-section" aria-labelledby="active-auctions-title">
        <div class="portal-section__head">
            <h2 id="active-auctions-title" class="portal-section__title">Active Auctions</h2>
            <?php if (!empty($active)): ?>
                <span class="portal-section__link"><?= count($active) ?> live</span>
            <?php endif; ?>
        </div>

        <?php if (empty($active)): ?>
            <div class="empty-state">
                <p class="empty-state-text">No active auctions yet. Create your first listing to start receiving bids.</p>
                <a href="<?= Url::to(['/auction/create']) ?>" class="btn btn-primary" style="margin-top:1rem;">Create Auction</a>
            </div>
        <?php else: ?>
            <div class="portal-lots">
                <?php foreach ($active as $auction): ?>
                    <?= $this->render('/partials/_portal_auction_card', ['auction' => $auction]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="portal-section" aria-labelledby="timed-out-title">
        <div class="portal-section__head">
            <h2 id="timed-out-title" class="portal-section__title">Timed Out</h2>
        </div>
        <p style="font-size:0.8125rem;color:var(--portal-text-muted);margin:-0.5rem 0 1rem;">No bids received or auction expired without a winner.</p>

        <?php if (empty($timedOut)): ?>
            <p style="color:var(--portal-text-muted);font-size:0.875rem;">No timed-out auctions.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Ended</th>
                            <th>Bids</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timedOut as $a): ?>
                            <tr>
                                <td>
                                    <a href="<?= Url::to(['/auction/view', 'id' => $a->auction_id]) ?>">
                                        <?= Html::encode($a->title) ?>
                                    </a>
                                </td>
                                <td class="td-date"><?= Html::encode(date('M j, Y H:i', strtotime((string) $a->end_time))) ?></td>
                                <td><?= (int) $a->total_bids ?></td>
                                <td><span class="badge badge-warning">Timed Out</span></td>
                                <td>
                                    <?php if ($a->canExtend()): ?>
                                        <a class="btn btn-primary btn-sm" href="<?= Url::to(['/auction/view', 'id' => $a->auction_id]) ?>">Extend</a>
                                    <?php else: ?>
                                        <a class="btn btn-outline btn-sm" href="<?= Url::to(['/auction/view', 'id' => $a->auction_id]) ?>">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="portal-section" aria-labelledby="closed-auctions-title">
        <div class="portal-section__head">
            <h2 id="closed-auctions-title" class="portal-section__title">Closed Auctions</h2>
        </div>

        <?php if (empty($closed)): ?>
            <p style="color:var(--portal-text-muted);font-size:0.875rem;">No closed auctions yet.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Final Price</th>
                            <th>Winner</th>
                            <th>Bids</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($closed as $a): ?>
                            <tr>
                                <td>
                                    <a href="<?= Url::to(['/auction/view', 'id' => $a->auction_id]) ?>">
                                        <?= Html::encode($a->title) ?>
                                    </a>
                                </td>
                                <td class="td-amount"><?= Auction::formatKes($a->final_price ?? $a->current_bid) ?></td>
                                <td>
                                    <?php if ($a->winner_first_name): ?>
                                        <?= Html::encode($a->winner_first_name . ' ' . $a->winner_last_name) ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) $a->total_bids ?></td>
                                <td><span class="badge badge-success">Closed</span></td>
                                <td>
                                    <?php if ($a->canExtend()): ?>
                                        <a class="btn btn-primary btn-sm" href="<?= Url::to(['/auction/view', 'id' => $a->auction_id]) ?>">Extend</a>
                                    <?php else: ?>
                                        <a class="btn btn-outline btn-sm" href="<?= Url::to(['/auction/view', 'id' => $a->auction_id]) ?>">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if (!empty($cancelled)): ?>
        <section class="portal-section" aria-labelledby="cancelled-title">
            <div class="portal-section__head">
                <h2 id="cancelled-title" class="portal-section__title">Cancelled / Suspended</h2>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Current Bid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cancelled as $a): ?>
                            <tr>
                                <td><?= Html::encode($a->title) ?></td>
                                <td><span class="badge badge-error"><?= Html::encode($a->status) ?></span></td>
                                <td class="td-amount"><?= Auction::formatKes($a->current_bid) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>
