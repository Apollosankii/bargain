<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var list<common\models\Auction> $featuredAuctions */

use common\models\Auction;
use yii\helpers\Html;
use yii\helpers\Url;

$this->render('_register_public_assets');

$this->title = 'Bargain — Where the right bid wins the right deal';

$heroAuction = $featuredAuctions[0] ?? null;
$isGuest = Yii::$app->user->isGuest;
?>
<div class="landing">
    <!-- Hero -->
    <section class="landing-hero" aria-labelledby="landing-hero-title">
        <div class="landing-hero__grid">
            <div class="landing-hero__copy">
                <p class="landing-eyebrow">THE SMART WAY TO BID</p>
                <h1 id="landing-hero-title" class="landing-hero__title">
                    Where the right bid<br>wins the right deal.
                </h1>
                <p class="landing-hero__lead">
                    Bargain is a real-time auction marketplace. Browse live listings,
                    place competitive bids, win items you want, and pay securely through the platform.
                </p>
                <div class="landing-hero__actions">
                    <a href="<?= Url::to(['/site/index', '#' => 'live-auctions']) ?>" class="landing-btn landing-btn--primary">
                        Explore Auctions
                    </a>
                    <a href="<?= Url::to(['/site/how-it-works']) ?>" class="landing-btn landing-btn--ghost">
                        How It Works
                    </a>
                </div>
            </div>

            <div class="landing-hero__visual" aria-label="Live auction preview">
                <?php if ($heroAuction !== null): ?>
                    <div class="landing-hero__frame">
                        <?php if ($heroAuction->image_url): ?>
                            <img
                                class="landing-hero__image"
                                src="<?= Html::encode($heroAuction->image_url) ?>"
                                alt="<?= Html::encode($heroAuction->title) ?>"
                            />
                        <?php else: ?>
                            <div class="landing-hero__image landing-hero__image--placeholder"></div>
                        <?php endif; ?>

                        <div class="landing-hero__chip landing-hero__chip--live">
                            <span class="landing-hero__pulse" aria-hidden="true"></span>
                            LIVE
                        </div>
                        <div class="landing-hero__chip landing-hero__chip--bid">
                            <span>Current bid</span>
                            <strong><?= Auction::formatKes($heroAuction->current_bid) ?></strong>
                        </div>
                        <div class="landing-hero__chip landing-hero__chip--bidders">
                            <span>Bidders</span>
                            <strong><?= (int) $heroAuction->total_bids ?></strong>
                        </div>
                        <div class="landing-hero__chip landing-hero__chip--time">
                            <span>Ending</span>
                            <strong><?= Html::encode(Auction::timeRemaining((string) $heroAuction->end_time)) ?></strong>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="landing-hero__frame landing-hero__frame--empty">
                        <div class="landing-hero__empty-label">Live auctions</div>
                        <p>No active auctions right now. Check back soon or join as an auctioneer to list items.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Live auctions -->
    <section class="landing-section landing-live" id="live-auctions" aria-labelledby="live-auctions-title">
        <div class="landing-section__head landing-section__head--split">
            <div>
                <h2 id="live-auctions-title" class="landing-section__title">What's going under the hammer</h2>
                <p class="landing-section__lead">
                    Discover auctions ending soon and find your next great deal.
                </p>
            </div>
            <a class="landing-section__link" href="<?= Url::to(['/site/signup']) ?>">
                View all auctions →
            </a>
        </div>

        <?php if (empty($featuredAuctions)): ?>
            <div class="landing-empty">
                <p>No live auctions at the moment. Create an account to get notified when new listings go live.</p>
                <a href="<?= Url::to(['/site/signup']) ?>" class="landing-btn landing-btn--primary">Join Bargain</a>
            </div>
        <?php else: ?>
            <div class="landing-lots">
                <?php foreach ($featuredAuctions as $auction): ?>
                    <?= $this->render('_landing_auction_card', [
                        'auction' => $auction,
                        'guest' => $isGuest,
                    ]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- How it works -->
    <section class="landing-section landing-process" aria-labelledby="process-title">
        <div class="landing-section__intro">
            <h2 id="process-title" class="landing-section__title">How Bargain works</h2>
            <p class="landing-section__lead">Three steps from browsing to owning.</p>
        </div>

        <ol class="landing-process__list">
            <li class="landing-process__item">
                <span class="landing-process__index">01</span>
                <div>
                    <h3>Discover</h3>
                    <p>Find an auction you're interested in — filter by category or search for what you need.</p>
                </div>
            </li>
            <li class="landing-process__item">
                <span class="landing-process__index">02</span>
                <div>
                    <h3>Bid</h3>
                    <p>Place competitive bids in real time. Get notified instantly if someone outbids you.</p>
                </div>
            </li>
            <li class="landing-process__item">
                <span class="landing-process__index">03</span>
                <div>
                    <h3>Win</h3>
                    <p>Win the auction and complete your payment securely through Bargain.</p>
                </div>
            </li>
        </ol>
    </section>

    <!-- Why Bargain -->
    <section class="landing-section landing-benefits" aria-labelledby="benefits-title">
        <h2 id="benefits-title" class="landing-section__title">Why Bargain</h2>
        <p class="landing-section__lead landing-section__lead--narrow">
            Built for real auctions — not generic listings. Trust, speed, and clarity at every step.
        </p>

        <div class="landing-benefits__grid">
            <div class="landing-benefit">
                <span class="landing-benefit__mark" aria-hidden="true">↗</span>
                <h3>Real-time bidding</h3>
                <p>See bids update as auctions happen. No stale prices or delayed results.</p>
            </div>
            <div class="landing-benefit">
                <span class="landing-benefit__mark" aria-hidden="true">◆</span>
                <h3>Secure payments</h3>
                <p>Pay via M-Pesa after you win. Every transaction is tracked on the platform.</p>
            </div>
            <div class="landing-benefit">
                <span class="landing-benefit__mark" aria-hidden="true">◎</span>
                <h3>Transparent auctions</h3>
                <p>Clear bid history, auction status, and time remaining — nothing hidden.</p>
            </div>
            <div class="landing-benefit">
                <span class="landing-benefit__mark" aria-hidden="true">⇄</span>
                <h3>Buyers &amp; sellers</h3>
                <p>One platform for the full auction journey — list, bid, win, and collect payment.</p>
            </div>
        </div>
    </section>

    <!-- User types -->
    <section class="landing-section landing-audience" aria-labelledby="audience-title">
        <h2 id="audience-title" class="landing-section__title landing-section__title--center">
            Are you here to buy or sell?
        </h2>

        <div class="landing-audience__split">
            <div class="landing-audience__panel landing-audience__panel--bidder">
                <p class="landing-audience__role">For Bidders</p>
                <h3>Find something worth bidding for.</h3>
                <p>Browse auctions, place bids, track your wins, and pay securely when you come out on top.</p>
                <a href="<?= Url::to(['/site/signup']) ?>" class="landing-btn landing-btn--primary">Start Bidding</a>
            </div>
            <div class="landing-audience__panel landing-audience__panel--auctioneer">
                <p class="landing-audience__role">For Auctioneers</p>
                <h3>Turn your items into opportunities.</h3>
                <p>Create auctions, monitor bids in real time, and receive payments when buyers complete checkout.</p>
                <a href="<?= Url::to(['/site/signup']) ?>" class="landing-btn landing-btn--ghost">Start Selling</a>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="landing-section landing-cta" aria-labelledby="cta-title">
        <div class="landing-cta__inner">
            <h2 id="cta-title" class="landing-cta__title">Your next great deal starts with a bid.</h2>
            <p class="landing-cta__lead">
                Join Bargain and discover a smarter way to buy and sell through auctions.
            </p>
            <div class="landing-cta__actions">
                <a href="<?= Url::to(['/site/index', '#' => 'live-auctions']) ?>" class="landing-btn landing-btn--primary">
                    Explore Auctions
                </a>
                <a href="<?= Url::to(['/site/signup']) ?>" class="landing-btn landing-btn--ghost">
                    Join Bargain
                </a>
            </div>
        </div>
    </section>
</div>
