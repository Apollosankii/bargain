<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\SupportTicket[] $tickets */
/** @var yii\data\Pagination $pagination */
/** @var array<string,string> $filters */
/** @var int $openCount */

use common\models\SupportTicket;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Support Inbox';
$badgeHtml = $openCount > 0
    ? '<span class="admin-badge admin-badge--warning">' . (int) $openCount . ' awaiting reply</span>'
    : null;
?>
<div class="admin-page">
    <?= $this->render('/partials/_admin_page_header', [
        'subtitle' => 'Reply to messages from bidders and auctioneers.',
        'badgeHtml' => $badgeHtml,
    ]) ?>

    <div class="admin-panel">
        <div class="admin-panel__filters">
            <form method="get" action="<?= Url::to(['/support/index']) ?>" class="row g-2">
                <div class="col-md-4">
                    <select class="form-select" name="status">
                        <option value="">All statuses</option>
                        <?php foreach (SupportTicket::statusLabels() as $val => $label): ?>
                            <option value="<?= Html::encode($val) ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>><?= Html::encode($label) ?></option>
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
                        <th>Updated</th>
                        <th>User</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td style="color:var(--admin-text-muted);font-size:0.8125rem;"><?= Html::encode(date('M j, Y H:i', strtotime((string) $t->updated_at))) ?></td>
                            <td>
                                <span class="fw-medium"><?= Html::encode($t->user?->getFullName() ?? '') ?></span>
                                <div style="font-size:0.75rem;color:var(--admin-text-muted);"><?= Html::encode($t->user?->email ?? '') ?></div>
                            </td>
                            <td><?= Html::encode($t->subject) ?></td>
                            <td><span class="admin-badge admin-badge--info"><?= Html::encode(SupportTicket::statusLabels()[$t->status] ?? $t->status) ?></span></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?= Url::to(['/support/view', 'id' => $t->ticket_id]) ?>">Open</a>
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
