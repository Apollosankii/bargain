<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Complaint[] $complaints */
/** @var yii\data\Pagination $pagination */
/** @var array<string,string> $filters */
/** @var int $openCount */

use common\models\Complaint;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Complaints';
$badgeHtml = $openCount > 0
    ? '<span class="admin-badge admin-badge--warning">' . (int) $openCount . ' open</span>'
    : null;
?>
<div class="admin-page">
    <?= $this->render('/partials/_admin_page_header', [
        'subtitle' => 'Review reports from bidders and auctioneers. Use complaint details as justification when taking moderation action.',
        'badgeHtml' => $badgeHtml,
    ]) ?>

    <div class="admin-panel">
        <div class="admin-panel__filters">
            <form method="get" action="<?= Url::to(['/complaint/index']) ?>" class="row g-2">
                <div class="col-md-4">
                    <select class="form-select" name="status">
                        <option value="">All statuses</option>
                        <?php foreach (Complaint::statusLabels() as $val => $label): ?>
                            <option value="<?= Html::encode($val) ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>><?= Html::encode($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="type">
                        <option value="">All types</option>
                        <?php foreach (Complaint::typeLabels() as $val => $label): ?>
                            <option value="<?= Html::encode($val) ?>" <?= $filters['type'] === $val ? 'selected' : '' ?>><?= Html::encode($label) ?></option>
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
                        <th>Reporter</th>
                        <th>Type</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($complaints as $c): ?>
                        <tr>
                            <td style="color:var(--admin-text-muted);font-size:0.8125rem;"><?= Html::encode(date('M j, Y', strtotime((string) $c->created_at))) ?></td>
                            <td class="fw-medium"><?= Html::encode($c->user?->getFullName() ?? 'User #' . $c->user_id) ?></td>
                            <td><span class="admin-badge admin-badge--muted"><?= Html::encode($c->type) ?></span></td>
                            <td><?= Html::encode($c->subject) ?></td>
                            <td><span class="admin-badge admin-badge--info"><?= Html::encode(Complaint::statusLabels()[$c->status] ?? $c->status) ?></span></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?= Url::to(['/complaint/view', 'id' => $c->complaint_id]) ?>">Review</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
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
