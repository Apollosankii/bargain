<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var array<string, mixed> $stats */

use common\models\Auction;
use common\models\Subscription;
use frontend\assets\ChartJsAsset;
use frontend\assets\PortalAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'My Stats — Bargain';

$planLabel = $stats['subscriptionPlan']
    ? (Subscription::planOptions()[$stats['subscriptionPlan']] ?? $stats['subscriptionPlan'])
    : 'None';

$range = (string) ($stats['chartRange'] ?? '30');
$charts = $stats['charts'] ?? ['labels' => [], 'bids' => [], 'bidders' => [], 'listings' => [], 'revenue' => []];
$rangeLabels = [
    '7' => '7 Days',
    '30' => '30 Days',
    '90' => '3 Months',
    'all' => '12 Months',
];

$chartPayload = [
    'labels' => $charts['labels'] ?? [],
    'bids' => $charts['bids'] ?? [],
    'bidders' => $charts['bidders'] ?? [],
    'listings' => $charts['listings'] ?? [],
    'revenue' => $charts['revenue'] ?? [],
    'auctionStatus' => [
        (int) $stats['active'],
        (int) $stats['closed'],
        (int) $stats['cancelled'],
    ],
    'payments' => [
        (int) ($stats['completedPayments'] ?? 0),
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
            <h1 class="portal-page__title">Analytics</h1>
            <p class="portal-page__subtitle">Performance overview for your auctions, bids, and earnings.</p>
        </div>
        <div class="portal-page__actions">
            <a href="<?= Url::to(['/dashboard/auctioneer']) ?>" class="btn btn-outline btn-sm">Back to auctions</a>
        </div>
    </header>

    <nav class="portal-range" aria-label="Chart time range">
        <?php foreach ($rangeLabels as $key => $label): ?>
            <a
                class="portal-range__btn<?= $range === $key ? ' is-active' : '' ?>"
                href="<?= Url::to(['/dashboard/auctioneer-stats', 'range' => $key]) ?>"
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
                    <?= (int) ($charts['bidTotal'] ?? 0) ?> bids received in this period.
                    Unique bidders are counted per day.
                </p>
                <div class="portal-chart__canvas-wrap portal-chart__canvas-wrap--tall">
                    <canvas id="chart-bids" aria-label="Bids and unique bidders over time"></canvas>
                </div>
            </div>
            <div class="portal-chart">
                <h3 class="portal-chart__title">Paid revenue</h3>
                <p class="portal-chart__hint">
                    Completed M-Pesa payments totaling
                    <?= Auction::formatKes($charts['revenueTotal'] ?? 0) ?> in this period.
                </p>
                <div class="portal-chart__canvas-wrap portal-chart__canvas-wrap--tall">
                    <canvas id="chart-revenue" aria-label="Paid revenue over time"></canvas>
                </div>
            </div>
        </div>
        <div class="portal-chart" style="margin-top:1rem;">
            <h3 class="portal-chart__title">Auctions listed</h3>
            <p class="portal-chart__hint">
                <?= (int) ($charts['listingTotal'] ?? 0) ?> listings created in this period.
            </p>
            <div class="portal-chart__canvas-wrap">
                <canvas id="chart-listings" aria-label="Auctions listed over time"></canvas>
            </div>
        </div>
    </section>

    <section class="portal-section">
        <div class="portal-section__head">
            <h2 class="portal-section__title">Composition</h2>
        </div>
        <div class="portal-charts-grid portal-charts-grid--3">
            <div class="portal-chart">
                <h3 class="portal-chart__title">Auction status</h3>
                <p class="portal-chart__hint">Current mix of all your auctions.</p>
                <div class="portal-chart__canvas-wrap">
                    <canvas id="chart-auction-status" aria-label="Auction status breakdown"></canvas>
                </div>
            </div>
            <div class="portal-chart">
                <h3 class="portal-chart__title">Payment outcomes</h3>
                <p class="portal-chart__hint">Count of paid, pending, and failed auction payments.</p>
                <div class="portal-chart__canvas-wrap">
                    <canvas id="chart-payments" aria-label="Payment outcomes"></canvas>
                </div>
            </div>
            <div class="portal-chart">
                <h3 class="portal-chart__title">By category</h3>
                <p class="portal-chart__hint">How your listings are distributed.</p>
                <?php if (empty($stats['byCategory'])): ?>
                    <p class="empty-state-text">No category data yet.</p>
                <?php else: ?>
                    <div class="portal-chart__canvas-wrap">
                        <canvas id="chart-category" aria-label="Auctions by category"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="portal-section">
        <div class="portal-section__head"><h2 class="portal-section__title">Auctions</h2></div>
        <div class="portal-stat-grid portal-stat-grid--auto">
            <?= $this->render('/partials/_portal_stat', ['label' => 'Total Auctions', 'value' => (int) $stats['totalAuctions'], 'icon' => 'muted', 'iconChar' => '◫']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Active', 'value' => (int) $stats['active'], 'icon' => 'brand', 'iconChar' => '◆']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Closed', 'value' => (int) $stats['closed'], 'icon' => 'success', 'iconChar' => '✓']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Cancelled', 'value' => (int) $stats['cancelled'], 'icon' => 'muted', 'iconChar' => '×']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Ending in 24h', 'value' => (int) $stats['endingSoon'], 'icon' => 'warning', 'iconChar' => '◷']) ?>
            <?= $this->render('/partials/_portal_stat', [
                'label' => 'With Bids / Zero',
                'value' => (int) $stats['auctionsWithBids'] . ' / ' . (int) $stats['zeroBidAuctions'],
                'icon' => 'muted',
                'iconChar' => '◎',
            ]) ?>
        </div>
    </section>

    <section class="portal-section">
        <div class="portal-section__head"><h2 class="portal-section__title">Bidding Activity</h2></div>
        <div class="portal-stat-grid portal-stat-grid--auto">
            <?= $this->render('/partials/_portal_stat', ['label' => 'Total Bids Received', 'value' => (int) $stats['totalBids'], 'icon' => 'brand', 'iconChar' => '↑']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Bids (7 days)', 'value' => (int) $stats['bidsLast7'], 'icon' => 'muted', 'iconChar' => '7']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Bids (30 days)', 'value' => (int) $stats['bidsLast30'], 'icon' => 'muted', 'iconChar' => '30']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Unique Bidders', 'value' => (int) $stats['uniqueBidders'], 'icon' => 'brand', 'iconChar' => '◉']) ?>
        </div>
    </section>

    <section class="portal-section">
        <div class="portal-section__head"><h2 class="portal-section__title">Earnings &amp; Payments</h2></div>
        <div class="portal-stat-grid portal-stat-grid--auto">
            <?= $this->render('/partials/_portal_stat', ['label' => 'Expected from Wins', 'value' => Auction::formatKes($stats['expectedEarnings']), 'icon' => 'muted', 'iconChar' => '◈']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Paid Revenue', 'value' => Auction::formatKes($stats['paidRevenue']), 'icon' => 'success', 'iconChar' => '◈']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Avg Final Price', 'value' => Auction::formatKes($stats['avgFinalPrice']), 'icon' => 'muted', 'iconChar' => '≈']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Pending Payments', 'value' => (int) $stats['pendingPayments'], 'icon' => 'warning', 'iconChar' => '◷']) ?>
            <?= $this->render('/partials/_portal_stat', ['label' => 'Failed Payments', 'value' => (int) $stats['failedPayments'], 'icon' => 'warning', 'iconChar' => '!']) ?>
        </div>
    </section>

    <section class="portal-section">
        <div class="portal-section__head"><h2 class="portal-section__title">Subscription</h2></div>
        <div class="portal-stat-grid">
            <?= $this->render('/partials/_portal_stat', ['label' => 'Current Plan', 'value' => Html::encode((string) $planLabel), 'icon' => 'brand', 'iconChar' => '◆']) ?>
            <?= $this->render('/partials/_portal_stat', [
                'label' => 'Days Remaining',
                'value' => $stats['subscriptionDaysLeft'] === null ? '—' : (int) $stats['subscriptionDaysLeft'],
                'icon' => 'muted',
                'iconChar' => '◷',
            ]) ?>
            <?= $this->render('/partials/_portal_stat', [
                'label' => 'Subscription Spend',
                'value' => Auction::formatKes($stats['subscriptionSpend']),
                'icon' => 'success',
                'iconChar' => '◈',
            ]) ?>
        </div>
    </section>
</div>
