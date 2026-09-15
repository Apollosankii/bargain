<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var array<string, mixed> $stats */

use common\models\Auction;
use frontend\assets\ChartJsAsset;
use frontend\assets\PortalAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'My Stats — Bargain';

$range = (string) ($stats['chartRange'] ?? '30');
$charts = $stats['charts'] ?? ['labels' => [], 'bids' => [], 'auctions' => [], 'listings' => [], 'revenue' => []];
$rangeLabels = [
    '7' => '7 Days',
    '30' => '30 Days',
    '90' => '3 Months',
    'all' => '12 Months',
];

$chartPayload = [
    'mode' => 'bidder',
    'labels' => $charts['labels'] ?? [],
    'bids' => $charts['bids'] ?? [],
    'auctions' => $charts['auctions'] ?? [],
    'listings' => $charts['listings'] ?? [],
    'revenue' => $charts['revenue'] ?? [],
    'bidStatus' => [
        (int) $stats['winning'],
        (int) $stats['outbid'],
        (int) $stats['won'],
        (int) $stats['withdrawn'],
    ],
    'payments' => [
        (int) $stats['completedPayments'],
        (int) $stats['pendingPayments'],
        (int) $stats['failedPayments'],
    ],
    'categories' => array_map(static fn (array $row): array => [
        'label' => (string) $row['category'],
        'total' => (int) $row['total'],
    ], $stats['byCategory'] ?? []),
];

ChartJsAsset::register($this);
$this->registerJsFile(
    '@web/js/portal-stats.js',
    ['depends' => [ChartJsAsset::class, PortalAsset::class], 'position' => \yii\web\View::POS_END],
);
?>
<div class="layout-content__inner portal-page">
    <header class="portal-page__header">
        <div>
            <h1 class="portal-page__title">Bidder Stats</h1>
            <p class="portal-page__subtitle">Your bidding activity, win rate, and payment history at a glance.</p>
        </div>
        <div class="portal-page__actions">
            <a href="<?= Url::to(['/dashboard/bidder']) ?>" class="btn btn-outline btn-sm">Back to dashboard</a>
        </div>
    </header>

    <nav class="portal-range" aria-label="Chart time range">
        <?php foreach ($rangeLabels as $key => $label): ?>
            <a
                class="portal-range__btn<?= $range === $key ? ' is-active' : '' ?>"
                href="<?= Url::to(['/dashboard/bidder-stats', 'range' => $key]) ?>"
            ><?= Html::encode($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <script type="application/json" id="portal-stats-data"><?= Json::encode($chartPayload) ?></script>

    <section class="portal-section">
        <div class="portal-section__head">
            <h2 class="portal-section__title">Activity over time</h2>
            <span class="portal-section__link"><?= Html::encode($rangeLabels[$range] ?? '30 Days') ?></span>
        </div>
        <div class="portal-charts-grid">
            <div class="portal-chart">
                <h3 class="portal-chart__title">Bidding activity</h3>
                <p class="portal-chart__hint">
                    <?= (int) ($charts['bidTotal'] ?? 0) ?> bids placed in this period.
                    Auctions counts distinct listings you bid on per day.
                </p>
                <div class="portal-chart__canvas-wrap portal-chart__canvas-wrap--tall">
                    <canvas id="chart-bids" aria-label="Bids placed over time"></canvas>
                </div>
            </div>
            <div class="portal-chart">
                <h3 class="portal-chart__title">Payments made</h3>
                <p class="portal-chart__hint">
                    Completed payments totaling
                    <?= Auction::formatKes($charts['revenueTotal'] ?? 0) ?> in this period.
                </p>
                <div class="portal-chart__canvas-wrap portal-chart__canvas-wrap--tall">
                    <canvas id="chart-revenue" aria-label="Payments over time"></canvas>
                </div>
            </div>
        </div>
        <div class="portal-chart" style="margin-top:1rem;">
            <h3 class="portal-chart__title">Bid volume</h3>
            <p class="portal-chart__hint">
                Total value of bids placed:
                <?= Auction::formatKes($charts['listingTotal'] ?? 0) ?> in this period.
            </p>
            <div class="portal-chart__canvas-wrap">
                <canvas id="chart-listings" aria-label="Bid volume over time"></canvas>
            </div>
        </div>
    </section>

    <section class="portal-section">
        <div class="portal-section__head">
            <h2 class="portal-section__title">Composition</h2>
        </div>
        <div class="portal-charts-grid portal-charts-grid--3">
            <div class="portal-chart">
                <h3 class="portal-chart__title">Bid outcomes</h3>
                <p class="portal-chart__hint">Current status of all your bids.</p>
                <div class="portal-chart__canvas-wrap">
                    <canvas id="chart-auction-status" aria-label="Bid status breakdown"></canvas>
                </div>
            </div>
            <div class="portal-chart">
                <h3 class="portal-chart__title">Payment outcomes</h3>
                <p class="portal-chart__hint">Paid, pending, and failed auction payments.</p>
                <div class="portal-chart__canvas-wrap">
                    <canvas id="chart-payments" aria-label="Payment outcomes"></canvas>
                </div>
            </div>
            <div class="portal-chart">
                <h3 class="portal-chart__title">By category</h3>
                <p class="portal-chart__hint">Auctions you've bid in by category.</p>
                <?php if (empty($stats['byCategory'])): ?>
                    <p class="empty-state-text">No category data yet.</p>
                <?php else: ?>
                    <div class="portal-chart__canvas-wrap">
                        <canvas id="chart-category" aria-label="Bids by category"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="portal-section">
        <div class="portal-section__head">
            <h2 class="portal-section__title">Bidding overview</h2>
        </div>
        <div class="portal-stat-grid portal-stat-grid--4">
            <?= $this->render('/partials/_portal_stat', ['label' => 'Total Bids', 'value' => (int) $stats['totalBids'], 'icon' => 'brand', 'iconChar' => '◆']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Auctions Bid On', 'value' => (int) $stats['auctionsBidOn'], 'icon' => 'muted', 'iconChar' => '◷']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Currently Winning', 'value' => (int) $stats['winning'], 'icon' => 'success', 'iconChar' => '◈']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Outbid', 'value' => (int) $stats['outbid'], 'icon' => 'warning', 'iconChar' => '◬']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Won', 'value' => (int) $stats['won'], 'icon' => 'success', 'iconChar' => '★']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Win Rate', 'value' => Html::encode((string) $stats['winRate']) . '%', 'icon' => 'brand', 'iconChar' => '%']) ?>
        </div>
    </section>

    <section class="portal-section">
        <div class="portal-section__head">
            <h2 class="portal-section__title">Activity</h2>
        </div>
        <div class="portal-stat-grid portal-stat-grid--4">
            <?= $this->render('/partials/_portal_stat', ['label' => 'Bids (7 days)', 'value' => (int) $stats['bidsLast7'], 'icon' => 'muted', 'iconChar' => '7']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Bids (30 days)', 'value' => (int) $stats['bidsLast30'], 'icon' => 'muted', 'iconChar' => '30']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Watchlist', 'value' => (int) $stats['watchlist'], 'icon' => 'brand', 'iconChar' => '◎']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Highest Bid', 'value' => Auction::formatKes($stats['highestBid']), 'icon' => 'warning', 'iconChar' => '▲']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Committed', 'value' => Auction::formatKes($stats['committed']), 'icon' => 'warning', 'iconChar' => '◉']) ?>
        </div>
    </section>

    <section class="portal-section">
        <div class="portal-section__head">
            <h2 class="portal-section__title">Payments</h2>
        </div>
        <div class="portal-stat-grid">
            <?= $this->render('/partials/_portal_stat', ['label' => 'Total Paid', 'value' => Auction::formatKes($stats['paidTotal']), 'icon' => 'success', 'iconChar' => '◈']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Completed', 'value' => (int) $stats['completedPayments'], 'icon' => 'success', 'iconChar' => '✓']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Pending', 'value' => (int) $stats['pendingPayments'], 'icon' => 'warning', 'iconChar' => '◷']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Failed', 'value' => (int) $stats['failedPayments'], 'icon' => 'muted', 'iconChar' => '×']) ?>
        </div>
    </section>
</div>
