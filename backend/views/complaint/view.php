<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Complaint $complaint */

use common\models\Auction;
use common\models\Complaint;
use common\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Complaint #' . $complaint->complaint_id;
$reasonDefault = $complaint->actionReasonSummary();
?>
<div class="admin-page">
    <div class="admin-page__back">
        <a href="<?= Url::to(['/complaint/index']) ?>" class="btn btn-sm btn-outline-secondary">&larr; All complaints</a>
    </div>

    <?= $this->render('/partials/_admin_page_header', [
        'subtitle' => 'Review the report and take moderation action if warranted.',
    ]) ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="admin-panel">
                <div class="admin-panel__head"><h3 class="admin-panel__title">Details</h3></div>
                <div class="admin-panel__body">
                    <dl class="row admin-detail-list mb-0">
                        <dt class="col-sm-4">Reporter</dt>
                        <dd class="col-sm-8"><?= Html::encode($complaint->user?->getFullName() ?? '') ?> (<?= Html::encode($complaint->user?->email ?? '') ?>)</dd>
                        <dt class="col-sm-4">Type</dt>
                        <dd class="col-sm-8"><?= Html::encode(Complaint::typeLabels()[$complaint->type] ?? $complaint->type) ?></dd>
                        <dt class="col-sm-4">Subject</dt>
                        <dd class="col-sm-8"><?= Html::encode($complaint->subject) ?></dd>
                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8"><?= nl2br(Html::encode($complaint->description)) ?></dd>
                        <dt class="col-sm-4">Filed</dt>
                        <dd class="col-sm-8"><?= Html::encode(date('M j, Y g:i A', strtotime((string) $complaint->created_at))) ?></dd>
                        <?php if ($complaint->auction): ?>
                            <dt class="col-sm-4">Auction</dt>
                            <dd class="col-sm-8"><?= Html::encode($complaint->auction->title) ?> (#<?= (int) $complaint->auction_id ?>)</dd>
                        <?php endif; ?>
                        <?php if ($complaint->reportedUser): ?>
                            <dt class="col-sm-4">Reported user</dt>
                            <dd class="col-sm-8"><?= Html::encode($complaint->reportedUser->getFullName()) ?> (<?= Html::encode($complaint->reportedUser->email) ?>)</dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <?= Html::beginForm(['/complaint/view', 'id' => $complaint->complaint_id], 'post') ?>
                <div class="admin-panel">
                    <div class="admin-panel__head"><h3 class="admin-panel__title">Review</h3></div>
                    <div class="admin-panel__body">
                        <div class="mb-3">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <?php foreach (Complaint::statusLabels() as $val => $label): ?>
                                    <option value="<?= Html::encode($val) ?>" <?= $complaint->status === $val ? 'selected' : '' ?>><?= Html::encode($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="admin_notes">Admin notes</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="4"><?= Html::encode((string) $complaint->admin_notes) ?></textarea>
                        </div>
                        <?= Html::submitButton('Save review', ['class' => 'btn btn-primary']) ?>
                    </div>
                </div>
            <?= Html::endForm() ?>
        </div>

        <div class="col-lg-5">
            <div class="admin-panel">
                <div class="admin-panel__head"><h3 class="admin-panel__title">Moderation actions</h3></div>
                <div class="admin-panel__body">
                    <p style="font-size:0.8125rem;color:var(--admin-text-secondary);">Use the complaint as documented reason when taking action.</p>

                    <?php if ($complaint->auction_id && $complaint->auction && $complaint->auction->status !== Auction::STATUS_CANCELLED): ?>
                        <?= Html::beginForm(['/admin/auction-status', 'id' => $complaint->auction_id], 'post', ['class' => 'mb-3']) ?>
                            <?= Html::hiddenInput('status', Auction::STATUS_CANCELLED) ?>
                            <label class="form-label" style="font-size:0.8125rem;">Take down auction</label>
                            <?= Html::textarea('reason', $reasonDefault, ['class' => 'form-control form-control-sm mb-2', 'rows' => 3, 'required' => true]) ?>
                            <?= Html::submitButton('Take down auction', ['class' => 'btn btn-sm btn-outline-danger']) ?>
                        <?= Html::endForm() ?>
                    <?php endif; ?>

                    <?php if ($complaint->reported_user_id && $complaint->reportedUser && $complaint->reportedUser->status === User::STATUS_ACTIVE && !$complaint->reportedUser->isAdmin()): ?>
                        <?= Html::beginForm(['/admin/user-status', 'id' => $complaint->reported_user_id], 'post') ?>
                            <?= Html::hiddenInput('status', User::STATUS_INACTIVE) ?>
                            <label class="form-label" style="font-size:0.8125rem;">Deactivate reported user</label>
                            <?= Html::textarea('reason', $reasonDefault, ['class' => 'form-control form-control-sm mb-2', 'rows' => 3, 'required' => true]) ?>
                            <?= Html::submitButton('Deactivate user', ['class' => 'btn btn-sm btn-outline-danger']) ?>
                        <?= Html::endForm() ?>
                    <?php endif; ?>

                    <?php if (
                        (!$complaint->auction_id || !$complaint->auction || $complaint->auction->status === Auction::STATUS_CANCELLED)
                        && (!$complaint->reported_user_id || !$complaint->reportedUser || $complaint->reportedUser->status !== User::STATUS_ACTIVE)
                    ): ?>
                        <p style="font-size:0.8125rem;color:var(--admin-text-muted);margin:0;">No direct moderation action available for this complaint.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($complaint->reviewer): ?>
                <div class="admin-panel">
                    <div class="admin-panel__body" style="font-size:0.8125rem;color:var(--admin-text-muted);">
                        Last reviewed by <?= Html::encode($complaint->reviewer->getFullName()) ?>
                        <?= $complaint->reviewed_at ? ' on ' . Html::encode(date('M j, Y g:i A', strtotime((string) $complaint->reviewed_at))) : '' ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
