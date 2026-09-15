<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Payment[] $payments */
/** @var string $status */

use common\models\Auction;
use common\models\Payment;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Payments';
$tabs = [
    Payment::STATUS_PENDING,
    Payment::STATUS_COMPLETED,
    Payment::STATUS_FAILED,
    Payment::STATUS_REFUNDED,
];
?>
<div class="admin-page">
    <?= $this->render('/partials/_admin_page_header', [
        'subtitle' => 'Review and update payment statuses for auction transactions.',
    ]) ?>

    <div class="admin-tabs">
        <?php foreach ($tabs as $t): ?>
            <a
                class="btn btn-sm <?= $t === $status ? 'btn-primary' : 'btn-outline-secondary' ?>"
                href="<?= Url::to(['/payment/index', 'status' => $t]) ?>"
            ><?= Html::encode($t) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($payments)): ?>
        <div class="admin-empty">No payments found for this status.</div>
    <?php else: ?>
        <div class="admin-panel">
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Auction</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td>#<?= (int) $p->payment_id ?></td>
                            <td>
                                <span class="fw-medium"><?= Html::encode(trim(($p->user?->first_name ?? '') . ' ' . ($p->user?->last_name ?? ''))) ?></span>
                                <div style="font-size:0.75rem;color:var(--admin-text-muted);"><?= Html::encode($p->user?->email ?? '') ?></div>
                            </td>
                            <td><?= Html::encode($p->auction?->title ?? ('Auction #' . $p->auction_id)) ?></td>
                            <td class="fw-medium"><?= Auction::formatKes($p->amount) ?></td>
                            <td><?= Html::encode($p->method) ?></td>
                            <td><span class="admin-badge admin-badge--muted"><?= Html::encode($p->status) ?></span></td>
                            <td style="color:var(--admin-text-muted);font-size:0.8125rem;">
                                <?= Html::encode(date('M j, Y H:i', strtotime((string) $p->created_at))) ?>
                            </td>
                            <td class="text-end">
                                <?= Html::beginForm(['/payment/update-status', 'id' => $p->payment_id], 'post', ['class' => 'd-inline-flex gap-2 align-items-center']) ?>
                                <select name="status" class="form-select form-select-sm" style="width:auto;">
                                    <?php foreach ($tabs as $t): ?>
                                        <option value="<?= Html::encode($t) ?>" <?= $t === $p->status ? 'selected' : '' ?>><?= Html::encode($t) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= Html::submitButton('Update', ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                <?= Html::endForm() ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
