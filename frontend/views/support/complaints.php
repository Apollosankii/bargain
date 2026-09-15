<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Complaint[] $complaints */

use common\models\Complaint;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Complaints — Bargain';
?>
<div class="layout-content__inner portal-page">
    <header class="portal-page__header">
        <div>
            <h1 class="portal-page__title">Complaints</h1>
            <p class="portal-page__subtitle">Report auctions or users that violate platform rules.</p>
        </div>
        <div class="portal-page__actions">
            <a href="<?= Url::to(['/support/create-complaint']) ?>" class="btn btn-primary btn-sm">File a complaint</a>
        </div>
    </header>

    <section class="portal-section">
        <?php if (empty($complaints)): ?>
            <div class="empty-state">
                <p class="empty-state-text">You have not filed any complaints yet.</p>
                <a href="<?= Url::to(['/support/create-complaint']) ?>" class="btn btn-outline btn-sm" style="margin-top:1rem;">File a complaint</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaints as $c): ?>
                            <tr>
                                <td style="color:var(--portal-text-muted);font-size:0.8125rem;white-space:nowrap;">
                                    <?= Html::encode(date('M j, Y', strtotime((string) $c->created_at))) ?>
                                </td>
                                <td><?= Html::encode(Complaint::typeLabels()[$c->type] ?? $c->type) ?></td>
                                <td><?= Html::encode($c->subject) ?></td>
                                <td><span class="badge <?= $c->statusBadgeClass() ?>"><?= Html::encode(Complaint::statusLabels()[$c->status] ?? $c->status) ?></span></td>
                                <td>
                                    <?php if ($c->type === Complaint::TYPE_AUCTION && $c->auction): ?>
                                        <a class="btn btn-outline btn-sm" href="<?= Url::to(['/auction/view', 'id' => $c->auction_id]) ?>">View auction</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
