<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Payment[] $payments */

use common\models\Auction;
use common\models\Payment;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'My Payments — Bargain';
?>
<div class="page">
    <div class="flex-between mb-2">
        <h1 class="page-title" style="margin:0;">My Payments</h1>
        <a href="<?= Url::to(['/dashboard/bidder']) ?>" class="btn btn-outline btn-sm">Back to browse</a>
    </div>

    <?php if (empty($payments)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">💳</div>
            <div class="empty-state-text">No payments yet.</div>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>Auction</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td>
                            <a href="<?= Url::to(['/payment/view', 'id' => $p->payment_id]) ?>">
                                <?= Html::encode($p->auction?->title ?? ('Auction #' . $p->auction_id)) ?>
                            </a>
                        </td>
                        <td><?= Auction::formatKes($p->amount) ?></td>
                        <td><?= Html::encode($p->method) ?></td>
                        <td><span class="badge <?= Payment::statusBadgeClass($p->status) ?>"><?= Html::encode($p->status) ?></span></td>
                        <td style="color:var(--text-muted);font-size:0.82rem;">
                            <?= Html::encode(date('M j, Y H:i', strtotime((string) $p->created_at))) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

