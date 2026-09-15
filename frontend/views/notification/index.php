<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Notification[] $notifications */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Notifications — Bargain';

$iconForType = static function (string $type): array {
    return match (true) {
        str_contains($type, 'BID') => ['bid', 'B'],
        str_contains($type, 'PAY') => ['pay', '$'],
        str_contains($type, 'CLOSE') => ['close', 'C'],
        default => ['system', '•'],
    };
};
?>
<div class="layout-content__inner portal-page portal-notifications">
    <header class="portal-page__header">
        <div>
            <h1 class="portal-page__title">Notifications</h1>
            <p class="portal-page__subtitle">Stay updated on bids, payments, and auction activity.</p>
        </div>
        <?php if (!empty($notifications)): ?>
            <div class="portal-page__actions">
                <?= Html::beginForm(['/notification/read-all'], 'post') ?>
                    <?= Html::submitButton('Mark all read', ['class' => 'btn btn-outline btn-sm']) ?>
                <?= Html::endForm() ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <p class="empty-state-text">No notifications yet. You'll see bid updates and payment alerts here.</p>
        </div>
    <?php else: ?>
        <div role="list">
            <?php foreach ($notifications as $n): ?>
                <?php [$iconClass, $iconChar] = $iconForType((string) $n->type); ?>
                <article
                    class="portal-notif<?= !$n->read_status ? ' is-unread' : ' is-read' ?>"
                    role="listitem"
                >
                    <?php if (!$n->read_status): ?>
                        <span class="portal-notif__unread-dot" aria-label="Unread"></span>
                    <?php endif; ?>

                    <div class="portal-notif__icon portal-notif__icon--<?= $iconClass ?>" aria-hidden="true">
                        <?= Html::encode($iconChar) ?>
                    </div>

                    <div class="portal-notif__body">
                        <div class="portal-notif__meta">
                            <span class="badge badge-muted"><?= Html::encode($n->type) ?></span>
                            <time class="portal-notif__time" datetime="<?= Html::encode((string) $n->created_at) ?>">
                                <?= Html::encode(date('M j, Y H:i', strtotime((string) $n->created_at))) ?>
                            </time>
                        </div>
                        <p class="portal-notif__message"><?= Html::encode($n->message) ?></p>
                        <div class="portal-notif__actions">
                            <?php if ($n->auction_id): ?>
                                <a class="btn btn-outline btn-sm" href="<?= Url::to(['/auction/view', 'id' => $n->auction_id]) ?>">
                                    View Auction
                                </a>
                            <?php endif; ?>
                            <?php if (!$n->read_status): ?>
                                <?= Html::beginForm(['/notification/read', 'id' => $n->notification_id], 'post') ?>
                                    <?= Html::submitButton('Mark read', ['class' => 'btn btn-primary btn-sm']) ?>
                                <?= Html::endForm() ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
