<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var array $categories */
/** @var common\models\Auction[] $auctions */
/** @var int|null $categoryId */
/** @var string $search */
/** @var int $unread */
/** @var list<int> $leadingAuctionIds */

use common\models\Auction;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Dashboard — Bargain';
/** @var \common\models\User $user */
$user = Yii::$app->user->identity;
$leadingAuctionIds = $leadingAuctionIds ?? [];
$leadingLookup = array_fill_keys($leadingAuctionIds, true);
$firstName = Html::encode($user->first_name ?? 'there');
?>
<div class="layout-content__inner portal-page">
    <header class="portal-page__header">
        <div>
            <h1 class="portal-page__greeting">Welcome back, <?= $firstName ?></h1>
            <p class="portal-page__subtitle">Browse live auctions and place your bids.</p>
        </div>
        <div class="portal-page__actions">
            <a href="<?= Url::to(['/dashboard/bidder-stats']) ?>" class="btn btn-outline btn-sm">My stats</a>
            <a href="<?= Url::to(['/bid/my-bids']) ?>" class="btn btn-primary btn-sm">My bids</a>
        </div>
    </header>

    <section class="portal-section" aria-labelledby="browse-title">
        <div class="portal-section__head">
            <h2 id="browse-title" class="portal-section__title">Browse auctions</h2>
        </div>

        <form method="get" action="<?= Url::to(['/dashboard/bidder']) ?>" class="portal-search" style="margin-bottom:1.25rem;">
            <input
                type="search"
                name="search"
                value="<?= Html::encode($search) ?>"
                placeholder="Search by title or description…"
                class="form-control"
                aria-label="Search auctions"
            />
            <?php if ($categoryId !== null): ?>
                <input type="hidden" name="category_id" value="<?= (int) $categoryId ?>" />
            <?php endif; ?>
            <button type="submit" class="btn btn-outline btn-sm">Search</button>
        </form>

        <div class="portal-category-bar" role="navigation" aria-label="Categories">
            <a
                class="portal-category-chip<?= $categoryId === null && $search === '' ? ' is-active' : '' ?>"
                href="<?= Url::to(['/dashboard/bidder']) ?>"
            >All</a>
            <?php foreach ($categories as $cat): ?>
                <a
                    class="portal-category-chip<?= (int) $categoryId === (int) $cat['category_id'] ? ' is-active' : '' ?>"
                    href="<?= Url::to(['/dashboard/bidder', 'category_id' => $cat['category_id']]) ?>"
                >
                    <?= Html::encode($cat['name']) ?>
                    <span class="portal-category-chip__count"><?= (int) $cat['active_auctions'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="portal-section" aria-labelledby="auctions-title">
        <div class="portal-section__head">
            <h2 id="auctions-title" class="portal-section__title">
                <?= $search !== '' ? 'Search results' : 'Live auctions' ?>
            </h2>
            <?php if (!empty($auctions)): ?>
                <span class="portal-section__link"><?= count($auctions) ?> listing<?= count($auctions) === 1 ? '' : 's' ?></span>
            <?php endif; ?>
        </div>

        <?php if (empty($auctions)): ?>
            <div class="empty-state">
                <p class="empty-state-text">No active auctions found. Try another category or search term.</p>
            </div>
        <?php else: ?>
            <div class="portal-lots">
                <?php foreach ($auctions as $auction): ?>
                    <?= $this->render('/partials/_portal_bidder_lot', [
                        'auction' => $auction,
                        'isLeading' => isset($leadingLookup[(int) $auction->auction_id]),
                        'isScheduled' => $auction->isScheduled(),
                    ]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
