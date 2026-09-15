<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var list<array<string, mixed>> $incoming */
/** @var list<array<string, mixed>> $subscriptions */

use common\models\Auction;
use common\models\Payment;
use common\models\SubscriptionPayment;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Transactions — Bargain';

$subBadge = static function (string $status): string {
    return match ($status) {
        SubscriptionPayment::STATUS_COMPLETED => 'badge-success',
        SubscriptionPayment::STATUS_FAILED, SubscriptionPayment::STATUS_CANCELLED => 'badge-error',
        default => 'badge-warning',
    };
};
?>
<div class="layout-content__inner portal-page">
    <header class="portal-page__header">
        <div>
            <h1 class="portal-page__title">Transactions</h1>
            <p class="portal-page__subtitle">Track incoming auction payments and your subscription billing history.</p>
        </div>
        <div class="portal-page__actions">
            <a href="<?= Url::to(['/dashboard/auctioneer']) ?>" class="btn btn-outline btn-sm">Back to auctions</a>
        </div>
    </header>

    <section class="portal-section">
        <div class="portal-section__head">
            <h2 class="portal-section__title">Incoming Auction Payments</h2>
        </div>
        <p style="font-size:0.8125rem;color:var(--portal-text-muted);margin:-0.5rem 0 1rem;">
            Money paid by winning bidders on your auctions.
        </p>

        <?php if (empty($incoming)): ?>
            <div class="empty-state">
                <p class="empty-state-text">No auction payments received yet.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Auction</th>
                            <th>Bidder</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Receipt</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incoming as $tx): ?>
                            <tr>
                                <td class="td-date"><?= Html::encode(date('M j, Y H:i', strtotime((string) $tx['date']))) ?></td>
                                <td>
                                    <a href="<?= Url::to($tx['auctionUrl']) ?>">
                                        <?= Html::encode((string) $tx['description']) ?>
                                    </a>
                                </td>
                                <td><?= Html::encode((string) $tx['counterparty']) ?></td>
                                <td class="td-amount"><?= Auction::formatKes($tx['amount']) ?></td>
                                <td><?= Html::encode((string) $tx['method']) ?></td>
                                <td class="td-receipt">
                                    <?= $tx['receipt'] ? Html::encode((string) $tx['receipt']) : '—' ?>
                                </td>
                                <td>
                                    <span class="badge <?= Payment::statusBadgeClass((string) $tx['status']) ?>">
                                        <?= Html::encode((string) $tx['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="portal-section">
        <div class="portal-section__head">
            <h2 class="portal-section__title">Subscription Payments</h2>
        </div>
        <p style="font-size:0.8125rem;color:var(--portal-text-muted);margin:-0.5rem 0 1rem;">
            What you paid for Bargain auctioneer plans.
        </p>

        <?php if (empty($subscriptions)): ?>
            <div class="empty-state">
                <p class="empty-state-text">No subscription payments yet.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Phone</th>
                            <th>Receipt</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $tx): ?>
                            <tr>
                                <td class="td-date"><?= Html::encode(date('M j, Y H:i', strtotime((string) $tx['date']))) ?></td>
                                <td><?= Html::encode((string) $tx['description']) ?></td>
                                <td class="td-amount"><?= Auction::formatKes($tx['amount']) ?></td>
                                <td><?= Html::encode((string) $tx['method']) ?></td>
                                <td class="td-receipt"><?= Html::encode((string) ($tx['phone'] ?? '—')) ?></td>
                                <td class="td-receipt">
                                    <?= $tx['receipt'] ? Html::encode((string) $tx['receipt']) : '—' ?>
                                </td>
                                <td>
                                    <span class="badge <?= $subBadge((string) $tx['status']) ?>">
                                        <?= Html::encode((string) $tx['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
