<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User[] $users */
/** @var yii\data\Pagination $usersPagination */
/** @var common\models\Auction[] $auctions */
/** @var yii\data\Pagination $auctionsPagination */
/** @var common\models\Notification[] $notifications */
/** @var array<string,int> $analytics */
/** @var array<string,string> $filters */

use common\models\Auction;
use common\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Manage Platform';
?>
<div class="admin-page">
    <?= $this->render('/partials/_admin_page_header', [
        'subtitle' => 'Review users, auctions, and platform activity. Deactivation and take-down actions require a documented reason.',
    ]) ?>

    <div class="admin-quick-grid">
        <div class="admin-quick-card">
            <div>
                <div class="admin-quick-card__label">Moderation</div>
                <div class="admin-quick-card__title">Review complaints</div>
            </div>
            <a class="btn btn-outline-primary btn-sm" href="<?= Url::to(['/complaint/index']) ?>">Open</a>
        </div>
        <div class="admin-quick-card">
            <div>
                <div class="admin-quick-card__label">Support</div>
                <div class="admin-quick-card__title">Reply to user messages</div>
            </div>
            <a class="btn btn-outline-primary btn-sm" href="<?= Url::to(['/support/index']) ?>">Open</a>
        </div>
        <div class="admin-quick-card">
            <div>
                <div class="admin-quick-card__label">Audit</div>
                <div class="admin-quick-card__title">View action history</div>
            </div>
            <a class="btn btn-outline-primary btn-sm" href="<?= Url::to(['/audit/index']) ?>">Open</a>
        </div>
        <div class="admin-quick-card">
            <div>
                <div class="admin-quick-card__label">Finance</div>
                <div class="admin-quick-card__title">Manage payments</div>
            </div>
            <a class="btn btn-outline-primary btn-sm" href="<?= Url::to(['/payment/index']) ?>">Open</a>
        </div>
    </div>

    <div class="admin-stat-grid">
        <?= $this->render('/partials/_admin_stat', ['label' => 'Users', 'value' => (int) $analytics['users'], 'icon' => 'brand', 'iconChar' => '◉']) ?>
        <?= $this->render('/partials/_admin_stat', ['label' => 'Active Auctions', 'value' => (int) $analytics['activeAuctions'], 'icon' => 'success', 'iconChar' => '◆']) ?>
        <?= $this->render('/partials/_admin_stat', ['label' => 'Total Bids', 'value' => (int) $analytics['bids'], 'icon' => 'brand', 'iconChar' => '↑']) ?>
        <?= $this->render('/partials/_admin_stat', ['label' => 'Pending Payments', 'value' => (int) $analytics['paymentsPending'], 'icon' => 'warning', 'iconChar' => '◷']) ?>
        <?= $this->render('/partials/_admin_stat', ['label' => 'Completed Payments', 'value' => (int) $analytics['paymentsCompleted'], 'icon' => 'success', 'iconChar' => '✓']) ?>
    </div>

    <div class="admin-panel">
        <div class="admin-panel__head">
            <h3 class="admin-panel__title">Users</h3>
        </div>
        <div class="admin-panel__filters">
            <form method="get" action="<?= Url::to(['/admin/index']) ?>" class="row g-2">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="user_q" placeholder="Search name, email, or phone" value="<?= Html::encode($filters['user_q']) ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="user_role">
                        <option value="">All roles</option>
                        <?php foreach ([User::ROLE_BIDDER, User::ROLE_AUCTIONEER, User::ROLE_ADMIN] as $role): ?>
                            <option value="<?= Html::encode($role) ?>" <?= $filters['user_role'] === $role ? 'selected' : '' ?>><?= Html::encode($role) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="user_status">
                        <option value="">All statuses</option>
                        <?php foreach ([User::STATUS_ACTIVE, User::STATUS_INACTIVE] as $st): ?>
                            <option value="<?= Html::encode($st) ?>" <?= $filters['user_status'] === $st ? 'selected' : '' ?>><?= Html::encode($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary" type="submit">Filter</button>
                </div>
                <input type="hidden" name="auction_q" value="<?= Html::encode($filters['auction_q']) ?>">
                <input type="hidden" name="auction_status" value="<?= Html::encode($filters['auction_status']) ?>">
            </form>
        </div>
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-medium"><?= Html::encode($u->getFullName()) ?></td>
                        <td style="color:var(--admin-text-secondary);"><?= Html::encode($u->email) ?></td>
                        <td><span class="admin-badge admin-badge--muted"><?= Html::encode($u->role) ?></span></td>
                        <td>
                            <span class="admin-badge <?= $u->status === User::STATUS_ACTIVE ? 'admin-badge--success' : 'admin-badge--warning' ?>">
                                <?= Html::encode($u->status) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <?php if (!$u->isAdmin()): ?>
                                <?php if ($u->status === User::STATUS_ACTIVE): ?>
                                    <?= Html::beginForm(['/admin/user-status', 'id' => $u->user_id], 'post', ['class' => 'd-inline-flex gap-1 align-items-center justify-content-end flex-wrap']) ?>
                                        <?= Html::hiddenInput('status', User::STATUS_INACTIVE) ?>
                                        <?= Html::textInput('reason', '', [
                                            'class' => 'form-control form-control-sm',
                                            'placeholder' => 'Reason (required)',
                                            'required' => true,
                                            'style' => 'min-width:180px;max-width:240px;',
                                            'aria-label' => 'Deactivation reason',
                                        ]) ?>
                                        <?= Html::submitButton('Deactivate', ['class' => 'btn btn-sm btn-outline-danger']) ?>
                                    <?= Html::endForm() ?>
                                <?php else: ?>
                                    <?= Html::beginForm(['/admin/user-status', 'id' => $u->user_id], 'post', ['class' => 'd-inline']) ?>
                                        <?= Html::hiddenInput('status', User::STATUS_ACTIVE) ?>
                                        <?= Html::submitButton('Reactivate', ['class' => 'btn btn-sm btn-outline-success']) ?>
                                    <?= Html::endForm() ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:var(--admin-text-muted);font-size:0.8125rem;">Protected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-panel__foot">
            <?= LinkPager::widget([
                'pagination' => $usersPagination,
                'options' => ['class' => 'pagination mb-0'],
                'linkContainerOptions' => ['class' => 'page-item'],
                'linkOptions' => ['class' => 'page-link'],
            ]) ?>
        </div>
    </div>

    <div class="admin-panel">
        <div class="admin-panel__head">
            <h3 class="admin-panel__title">Auctions</h3>
        </div>
        <div class="admin-panel__filters">
            <form method="get" action="<?= Url::to(['/admin/index']) ?>" class="row g-2">
                <input type="hidden" name="user_q" value="<?= Html::encode($filters['user_q']) ?>">
                <input type="hidden" name="user_role" value="<?= Html::encode($filters['user_role']) ?>">
                <input type="hidden" name="user_status" value="<?= Html::encode($filters['user_status']) ?>">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="auction_q" placeholder="Search title or description" value="<?= Html::encode($filters['auction_q']) ?>">
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="auction_status">
                        <option value="">All statuses</option>
                        <?php foreach ([Auction::STATUS_ACTIVE, Auction::STATUS_CLOSED, Auction::STATUS_CANCELLED] as $st): ?>
                            <option value="<?= Html::encode($st) ?>" <?= $filters['auction_status'] === $st ? 'selected' : '' ?>><?= Html::encode($st) ?></option>
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
                        <th>Title</th>
                        <th>Status</th>
                        <th>Current Bid</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($auctions as $a): ?>
                    <tr>
                        <td class="fw-medium"><?= Html::encode($a->title) ?></td>
                        <td>
                            <?php
                            $cls = match ($a->status) {
                                Auction::STATUS_ACTIVE => 'admin-badge--success',
                                Auction::STATUS_CANCELLED => 'admin-badge--danger',
                                default => 'admin-badge--muted',
                            };
                            ?>
                            <span class="admin-badge <?= $cls ?>"><?= Html::encode($a->status) ?></span>
                        </td>
                        <td><?= Html::encode(Auction::formatKes($a->current_bid)) ?></td>
                        <td class="text-end">
                            <?php if ($a->status === Auction::STATUS_CANCELLED): ?>
                                <?= Html::beginForm(['/admin/auction-status', 'id' => $a->auction_id], 'post', ['class' => 'd-inline']) ?>
                                    <?= Html::hiddenInput('status', Auction::STATUS_ACTIVE) ?>
                                    <?= Html::submitButton('Reactivate', ['class' => 'btn btn-sm btn-outline-success']) ?>
                                <?= Html::endForm() ?>
                            <?php else: ?>
                                <?= Html::beginForm(['/admin/auction-status', 'id' => $a->auction_id], 'post', ['class' => 'd-inline-flex gap-1 align-items-center justify-content-end flex-wrap']) ?>
                                    <?= Html::hiddenInput('status', Auction::STATUS_CANCELLED) ?>
                                    <?= Html::textInput('reason', '', [
                                        'class' => 'form-control form-control-sm',
                                        'placeholder' => 'Reason (required)',
                                        'required' => true,
                                        'style' => 'min-width:180px;max-width:240px;',
                                        'aria-label' => 'Take-down reason',
                                    ]) ?>
                                    <?= Html::submitButton(
                                        $a->status === Auction::STATUS_CLOSED ? 'Take down' : 'Deactivate',
                                        ['class' => 'btn btn-sm btn-outline-danger'],
                                    ) ?>
                                <?= Html::endForm() ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-panel__foot">
            <?= LinkPager::widget([
                'pagination' => $auctionsPagination,
                'options' => ['class' => 'pagination mb-0'],
                'linkContainerOptions' => ['class' => 'page-item'],
                'linkOptions' => ['class' => 'page-link'],
            ]) ?>
        </div>
    </div>

    <div class="admin-panel">
        <div class="admin-panel__head">
            <h3 class="admin-panel__title">Recent Notifications</h3>
        </div>
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>User</th>
                        <th>Message</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($notifications as $n): ?>
                    <tr>
                        <td><span class="admin-badge admin-badge--muted"><?= Html::encode($n->type) ?></span></td>
                        <td style="color:var(--admin-text-secondary);"><?= Html::encode($n->user?->email ?? ('User #' . $n->user_id)) ?></td>
                        <td style="font-size:0.8125rem;"><?= Html::encode($n->message) ?></td>
                        <td style="color:var(--admin-text-muted);font-size:0.8125rem;white-space:nowrap;"><?= Html::encode(date('M j, H:i', strtotime((string) $n->created_at))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
