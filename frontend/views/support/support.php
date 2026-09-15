<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\SupportTicket[] $tickets */

use common\models\SupportTicket;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Contact Support — Bargain';
?>
<div class="layout-content__inner portal-page">
    <header class="portal-page__header">
        <div>
            <h1 class="portal-page__title">Contact Support</h1>
            <p class="portal-page__subtitle">Message our admin team for help with your account, payments, or auctions.</p>
        </div>
        <div class="portal-page__actions">
            <a href="<?= Url::to(['/support/create-ticket']) ?>" class="btn btn-primary btn-sm">New message</a>
        </div>
    </header>

    <section class="portal-section">
        <?php if (empty($tickets)): ?>
            <div class="empty-state">
                <p class="empty-state-text">You have no support conversations yet.</p>
                <a href="<?= Url::to(['/support/create-ticket']) ?>" class="btn btn-outline btn-sm" style="margin-top:1rem;">Contact support</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Updated</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td style="color:var(--portal-text-muted);font-size:0.8125rem;white-space:nowrap;">
                                    <?= Html::encode(date('M j, Y H:i', strtotime((string) $t->updated_at))) ?>
                                </td>
                                <td><?= Html::encode($t->subject) ?></td>
                                <td><span class="badge <?= $t->statusBadgeClass() ?>"><?= Html::encode(SupportTicket::statusLabels()[$t->status] ?? $t->status) ?></span></td>
                                <td>
                                    <a class="btn btn-outline btn-sm" href="<?= Url::to(['/support/view-ticket', 'id' => $t->ticket_id]) ?>">Open</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
