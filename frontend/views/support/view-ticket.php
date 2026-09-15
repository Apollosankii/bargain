<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\SupportTicket $ticket */
/** @var common\models\SupportMessage[] $messages */

use common\models\SupportTicket;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Html::encode($ticket->subject) . ' — Support';
$isClosed = $ticket->status === SupportTicket::STATUS_CLOSED;
?>
<div class="layout-content__inner portal-page">
    <header class="portal-page__header">
        <div>
            <h1 class="portal-page__title"><?= Html::encode($ticket->subject) ?></h1>
            <p class="portal-page__subtitle">
                <span class="badge <?= $ticket->statusBadgeClass() ?>"><?= Html::encode(SupportTicket::statusLabels()[$ticket->status] ?? $ticket->status) ?></span>
            </p>
        </div>
        <div class="portal-page__actions">
            <a href="<?= Url::to(['/support/support']) ?>" class="btn btn-outline btn-sm">All conversations</a>
        </div>
    </header>

    <section class="portal-section">
        <div class="support-thread">
            <?php foreach ($messages as $msg): ?>
                <article class="support-message <?= $msg->is_admin ? 'support-message--admin' : 'support-message--user' ?>">
                    <div class="support-message__meta">
                        <?= $msg->is_admin ? 'Support team' : 'You' ?>
                        · <?= Html::encode(date('M j, Y g:i A', strtotime((string) $msg->created_at))) ?>
                    </div>
                    <div class="support-message__body"><?= nl2br(Html::encode($msg->body)) ?></div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if (!$isClosed): ?>
            <?= Html::beginForm(['/support/reply', 'id' => $ticket->ticket_id], 'post', ['class' => 'portal-form', 'style' => 'max-width:640px;margin-top:1.5rem;']) ?>
                <div class="form-group">
                    <label class="form-label" for="reply-body">Your reply</label>
                    <textarea id="reply-body" name="body" class="form-control" rows="4" required placeholder="Write a reply…"></textarea>
                </div>
                <?= Html::submitButton('Send reply', ['class' => 'btn btn-primary btn-sm']) ?>
            <?= Html::endForm() ?>
        <?php else: ?>
            <p style="color:var(--portal-text-muted);font-size:0.875rem;margin-top:1rem;">This conversation is closed. Open a new ticket if you need further help.</p>
        <?php endif; ?>
    </section>
</div>
