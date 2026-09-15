<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var list<array<string, mixed>> $transactions */

use common\models\Auction;
use common\models\Payment;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Transactions — Bargain';
?>
<div class="layout-content__inner portal-page">
    <header class="portal-page__header">
        <div>
            <h1 class="portal-page__title">My Transactions</h1>
            <p class="portal-page__subtitle">Payments you made for auctions you won.</p>
        </div>
        <div class="portal-page__actions">
            <a href="<?= Url::to(['/dashboard/bidder']) ?>" class="btn btn-outline btn-sm">Back to dashboard</a>
        </div>
    </header>

    <section class="portal-section">
        <?php if (empty($transactions)): ?>
            <div class="empty-state">
                <p class="empty-state-text">No transactions yet.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Receipt</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td style="color:var(--portal-text-muted);font-size:0.82rem;white-space:nowrap;">
                                    <?= Html::encode(date('M j, Y H:i', strtotime((string) $tx['date']))) ?>
                                </td>
                                <td><?= Html::encode((string) $tx['type']) ?></td>
                                <td>
                                    <a href="<?= Url::to($tx['auctionUrl']) ?>">
                                        <?= Html::encode((string) $tx['description']) ?>
                                    </a>
                                </td>
                                <td><?= Auction::formatKes($tx['amount']) ?></td>
                                <td><?= Html::encode((string) $tx['method']) ?></td>
                                <td style="font-size:0.82rem;">
                                    <?= $tx['receipt'] ? Html::encode((string) $tx['receipt']) : '—' ?>
                                </td>
                                <td>
                                    <span class="badge <?= Payment::statusBadgeClass((string) $tx['status']) ?>">
                                        <?= Html::encode((string) $tx['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a class="btn btn-outline btn-sm" href="<?= Url::to($tx['viewUrl']) ?>">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
