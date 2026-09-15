<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\SupportTicket $ticket */
/** @var common\models\SupportMessage[] $messages */

use common\models\SupportTicket;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $ticket->subject;
$isClosed = $ticket->status === SupportTicket::STATUS_CLOSED;

$actionsHtml = '';
if (!$isClosed) {
    ob_start();
    echo Html::beginForm(['/support/close', 'id' => $ticket->ticket_id], 'post', ['class' => 'd-inline']);
    echo Html::submitButton('Close ticket', [
        'class' => 'btn btn-sm btn-outline-secondary',
        'data-confirm' => 'Close this ticket?',
    ]);
    echo Html::endForm();
    $actionsHtml = ob_get_clean();
}

$subtitle = 'From ' . Html::encode($ticket->user?->getFullName() ?? '')
    . ' (' . Html::encode($ticket->user?->email ?? '') . ') · '
    . '<span class="admin-badge admin-badge--info">'
    . Html::encode(SupportTicket::statusLabels()[$ticket->status] ?? $ticket->status)
    . '</span>';
?>
<div class="admin-page">
    <div class="admin-page__back">
        <a href="<?= Url::to(['/support/index']) ?>" class="btn btn-sm btn-outline-secondary">&larr; Support inbox</a>
    </div>

    <?= $this->render('/partials/_admin_page_header', [
        'subtitle' => $subtitle,
        'actionsHtml' => $actionsHtml,
    ]) ?>

    <div class="admin-panel">
        <div class="admin-panel__body">
            <?php foreach ($messages as $msg): ?>
                <div class="admin-thread-item">
                    <div class="admin-thread-item__meta">
                        <?= $msg->is_admin ? 'Admin' : Html::encode($msg->sender?->getFullName() ?? 'User') ?>
                        · <?= Html::encode(date('M j, Y g:i A', strtotime((string) $msg->created_at))) ?>
                    </div>
                    <div style="font-size:0.9375rem;line-height:1.55;"><?= nl2br(Html::encode($msg->body)) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!$isClosed): ?>
        <?= Html::beginForm(['/support/reply', 'id' => $ticket->ticket_id], 'post') ?>
            <div class="admin-panel">
                <div class="admin-panel__head"><h3 class="admin-panel__title">Reply</h3></div>
                <div class="admin-panel__body">
                    <textarea id="body" name="body" class="form-control mb-3" rows="4" required placeholder="Write your reply…"></textarea>
                    <?= Html::submitButton('Send reply', ['class' => 'btn btn-primary']) ?>
                </div>
            </div>
        <?= Html::endForm() ?>
    <?php endif; ?>
</div>
