<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var list<array<string, mixed>> $rows */
/** @var yii\data\Pagination $pagination */
/** @var array<string,string> $filters */
/** @var list<string> $targetTypes */
/** @var list<string> $actions */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Audit Log';
?>
<div class="admin-page">
    <?= $this->render('/partials/_admin_page_header', [
        'subtitle' => 'Every admin action is logged here with the reason provided at the time of deactivation or take-down.',
    ]) ?>

    <div class="admin-panel">
        <div class="admin-panel__filters">
            <form method="get" action="<?= Url::to(['/audit/index']) ?>" class="row g-2">
                <div class="col-md-4">
                    <select class="form-select" name="target_type">
                        <option value="">All targets</option>
                        <?php foreach ($targetTypes as $t): ?>
                            <option value="<?= Html::encode($t) ?>" <?= $filters['target_type'] === $t ? 'selected' : '' ?>><?= Html::encode($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="action">
                        <option value="">All actions</option>
                        <?php foreach ($actions as $a): ?>
                            <option value="<?= Html::encode($a) ?>" <?= $filters['action'] === $a ? 'selected' : '' ?>><?= Html::encode($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary" type="submit">Filter</button>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Admin</th>
                        <th>Target</th>
                        <th>ID</th>
                        <th>Action</th>
                        <th>Reason / Justification</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="6" class="admin-empty">No audit records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td style="color:var(--admin-text-muted);font-size:0.8125rem;white-space:nowrap;">
                                    <?= Html::encode(date('M j, Y g:i A', strtotime((string) $row['performed_at']))) ?>
                                </td>
                                <td>
                                    <span class="fw-medium"><?= Html::encode((string) ($row['admin_name'] ?? '')) ?></span>
                                    <div style="font-size:0.75rem;color:var(--admin-text-muted);"><?= Html::encode((string) ($row['admin_email'] ?? '')) ?></div>
                                </td>
                                <td><span class="admin-badge admin-badge--muted"><?= Html::encode((string) $row['target_type']) ?></span></td>
                                <td><?= (int) $row['target_id'] ?></td>
                                <td>
                                    <?php
                                    $actionBadge = match ((string) $row['action']) {
                                        'DEACTIVATE', 'REMOVE' => 'admin-badge--danger',
                                        'REACTIVATE' => 'admin-badge--success',
                                        default => 'admin-badge--info',
                                    };
                                    ?>
                                    <span class="admin-badge <?= $actionBadge ?>"><?= Html::encode((string) $row['action']) ?></span>
                                </td>
                                <td style="font-size:0.8125rem;">
                                    <?= !empty($row['reason']) ? Html::encode((string) $row['reason']) : '<span style="color:var(--admin-text-muted);">—</span>' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-panel__foot">
            <?= LinkPager::widget([
                'pagination' => $pagination,
                'options' => ['class' => 'pagination mb-0'],
                'linkContainerOptions' => ['class' => 'page-item'],
                'linkOptions' => ['class' => 'page-link'],
            ]) ?>
        </div>
    </div>
</div>
