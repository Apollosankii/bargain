<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var list<common\models\Auction> $featuredAuctions */

use yii\helpers\Html;
use yii\helpers\Url;

$this->render('_register_public_assets');

$this->title = 'How It Works — Bargain';

$steps = [
    [
        'number' => '01',
        'title' => 'Create Your Account',
        'text' => 'Register as a Bidder or Auctioneer in minutes. Verify your details, choose your role, and get access to live auctions or start listing items to sell.',
        'label' => 'Account setup',
        'reverse' => false,
    ],
    [
        'number' => '02',
        'title' => 'Find an Auction',
        'text' => 'Browse live listings by category or search. Review photos, descriptions, starting bids and time remaining before you commit to a lot.',
        'label' => 'Browse listings',
        'reverse' => true,
    ],
    [
        'number' => '03',
        'title' => 'Place Your Bid',
        'text' => 'Enter a competitive bid on the items you want. Stay in the running with real-time updates and get notified instantly if someone outbids you.',
        'label' => 'Live bidding',
        'reverse' => false,
    ],
    [
        'number' => '04',
        'title' => 'Win, Pay & Collect',
        'text' => 'When the hammer falls in your favour, complete payment through the platform via M-Pesa or other supported methods, then arrange collection with the seller.',
        'label' => 'Secure checkout',
        'reverse' => true,
    ],
];
?>
<div class="marketing">
    <header class="marketing-page__hero">
        <div class="marketing-page__hero-inner">
            <p class="marketing-eyebrow">How Bargain Works</p>
            <h1 class="marketing-page__title marketing-page__title--wide">
                A simpler way to buy,<br>sell and win.
            </h1>
            <p class="marketing-page__lead">
                Bargain connects buyers and sellers through live auctions — browse listings,
                place competitive bids in real time, and complete payment securely when you win.
            </p>
        </div>
    </header>

    <div class="marketing-timeline">
        <?php foreach ($steps as $i => $step): ?>
            <?php $auction = $featuredAuctions[$i] ?? null; ?>
            <article class="marketing-step<?= $step['reverse'] ? ' marketing-step--reverse' : '' ?>">
                <div class="marketing-step__copy">
                    <p class="marketing-step__number"><?= $step['number'] ?></p>
                    <h2 class="marketing-step__title"><?= Html::encode($step['title']) ?></h2>
                    <p class="marketing-step__text"><?= Html::encode($step['text']) ?></p>
                </div>
                <div class="marketing-step__visual<?= ($auction === null || !$auction->image_url) ? ' marketing-step__visual--placeholder' : '' ?>" aria-hidden="true">
                    <?php if ($auction !== null && $auction->image_url): ?>
                        <img src="<?= Html::encode($auction->image_url) ?>" alt="" />
                    <?php else: ?>
                        <span class="marketing-step__visual-label"><?= Html::encode($step['label']) ?></span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <section class="marketing-cta" aria-labelledby="hiw-cta-title">
        <div class="marketing-cta__inner">
            <div class="marketing-cta__copy">
                <h2 id="hiw-cta-title">Ready to find your next deal?</h2>
                <p>Join thousands of buyers and sellers on Kenya's auction marketplace.</p>
            </div>
            <div class="marketing-cta__actions">
                <a href="<?= Url::to(['/site/index', '#' => 'live-auctions']) ?>" class="landing-btn landing-btn--primary">
                    Explore Auctions
                </a>
                <a href="<?= Url::to(['/site/signup']) ?>" class="landing-btn landing-btn--ghost">
                    Create an Account
                </a>
            </div>
        </div>
    </section>
</div>
