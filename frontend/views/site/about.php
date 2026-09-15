<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Url;

$this->render('_register_public_assets');

$this->title = 'About — Bargain';
?>
<div class="marketing">
    <header class="marketing-page__hero">
        <div class="marketing-page__hero-inner">
            <p class="marketing-eyebrow">About Bargain</p>
            <h1 class="marketing-page__title marketing-page__title--wide">
                Making online auctions<br>simpler, clearer and fairer.
            </h1>
            <p class="marketing-page__lead">
                Bargain is a smart online auctioneering platform that connects sellers and buyers
                through real-time competitive bidding — with transparent listings, secure payments,
                and a dashboard that keeps everyone informed.
            </p>
        </div>
    </header>

    <section class="marketing-principles" aria-labelledby="principles-title">
        <div class="marketing-principles__intro">
            <p id="principles-title">
                We built Bargain around four principles that make the experience trustworthy
                for both sides of every auction.
            </p>
        </div>

        <div class="marketing-principles__grid">
            <article class="marketing-principle">
                <p class="marketing-principle__number">01</p>
                <h2 class="marketing-principle__title">List Anything</h2>
                <p class="marketing-principle__text">
                    Auctioneers can list any product with multiple images, clear descriptions
                    and a starting price — reaching buyers who are ready to bid.
                </p>
            </article>

            <article class="marketing-principle">
                <p class="marketing-principle__number">02</p>
                <h2 class="marketing-principle__title">Real-Time Bidding</h2>
                <p class="marketing-principle__text">
                    Bidders compete live as the clock runs down. Get instantly notified when
                    you are outbid so you never miss a lot you care about.
                </p>
            </article>

            <article class="marketing-principle">
                <p class="marketing-principle__number">03</p>
                <h2 class="marketing-principle__title">Secure Payments</h2>
                <p class="marketing-principle__text">
                    Pay via M-Pesa, card or bank transfer. All transactions are tracked,
                    recorded and secured through the platform.
                </p>
            </article>

            <article class="marketing-principle">
                <p class="marketing-principle__number">04</p>
                <h2 class="marketing-principle__title">Full Transparency</h2>
                <p class="marketing-principle__text">
                    See bid history, auction stats and earnings in one dashboard —
                    no hidden steps, no guesswork.
                </p>
            </article>
        </div>
    </section>

    <section class="marketing-cta" aria-labelledby="about-cta-title">
        <div class="marketing-cta__inner">
            <div class="marketing-cta__copy">
                <h2 id="about-cta-title">See what&apos;s live right now</h2>
                <p>Browse active auctions or create an account to start bidding or selling.</p>
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
