<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Url;

$this->render('_register_public_assets');

$this->title = 'Terms & Conditions — Bargain';

$sections = [
    'account' => [
        'number' => '01',
        'title' => 'Account & Registration',
        'paragraphs' => [
            'All users must provide accurate registration information when creating a Bargain account. False, misleading or incomplete information may result in account suspension or permanent removal from the platform.',
            'You are responsible for maintaining the confidentiality of your login credentials and for all activity that occurs under your account.',
        ],
    ],
    'listings' => [
        'number' => '02',
        'title' => 'Auction Listings',
        'paragraphs' => [
            'Auctioneers are solely responsible for the accuracy of their product listings, including descriptions, images, condition and starting prices.',
            'Misleading, fraudulent or prohibited listings will be removed without notice. Bargain reserves the right to reject or cancel any listing that violates platform policies.',
        ],
    ],
    'bidding' => [
        'number' => '03',
        'title' => 'Bidding',
        'paragraphs' => [
            'All bids placed on Bargain are legally binding commitments to purchase the item at the bid amount, subject to winning the auction.',
            'Bidders must exercise due diligence before placing a bid. Once submitted, a bid cannot be retracted except where required by applicable law.',
        ],
    ],
    'payments' => [
        'number' => '04',
        'title' => 'Payments',
        'paragraphs' => [
            'Winning bidders are obligated to complete payment within 48 hours of the auction closing through the platform\'s supported payment methods.',
            'Failure to complete payment within the required timeframe may result in account restrictions and forfeiture of the winning bid.',
        ],
    ],
    'suspension' => [
        'number' => '05',
        'title' => 'Account Suspension',
        'paragraphs' => [
            'Bargain reserves the right to suspend or terminate any account or listing that violates these terms, platform policies, or applicable law.',
            'Suspended users may not create new accounts to circumvent restrictions. Bargain may take further action to protect the integrity of the marketplace.',
        ],
    ],
    'disputes' => [
        'number' => '06',
        'title' => 'Disputes & Liability',
        'paragraphs' => [
            'All disputes between buyers and sellers must be reported to Bargain support for resolution. Users agree to cooperate in good faith during any dispute process.',
            'Bargain is not responsible for the condition, authenticity or delivery of items sold through the platform. Buyers must exercise due diligence before bidding. Bargain\'s liability is limited to the extent permitted by applicable law.',
        ],
    ],
];
?>
<div class="marketing">
    <header class="marketing-page__hero">
        <div class="marketing-page__hero-inner">
            <p class="marketing-eyebrow">Legal</p>
            <h1 class="marketing-page__title">Terms &amp; Conditions</h1>
            <p class="marketing-page__meta">Last updated: August 2026</p>
            <p class="marketing-page__lead" style="margin-top: 1.25rem;">
                By using Bargain you agree to the following terms. Please read them carefully
                before creating an account or participating in any auction.
            </p>
        </div>
    </header>

    <div class="marketing-terms">
        <div class="marketing-terms__layout">
            <aside class="marketing-terms__toc" aria-label="Table of contents">
                <p class="marketing-terms__toc-label">Contents</p>
                <nav>
                    <?php foreach ($sections as $id => $section): ?>
                        <a href="#terms-<?= $id ?>"><?= $section['number'] ?> — <?= $section['title'] ?></a>
                    <?php endforeach; ?>
                </nav>
            </aside>

            <div class="marketing-terms__body">
                <?php foreach ($sections as $id => $section): ?>
                    <section class="marketing-terms__section" id="terms-<?= $id ?>">
                        <p class="marketing-terms__section-number"><?= $section['number'] ?></p>
                        <h2 class="marketing-terms__heading"><?= $section['title'] ?></h2>
                        <?php foreach ($section['paragraphs'] as $paragraph): ?>
                            <p><?= $paragraph ?></p>
                        <?php endforeach; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <section class="marketing-cta" aria-labelledby="terms-cta-title">
        <div class="marketing-cta__inner">
            <div class="marketing-cta__copy">
                <h2 id="terms-cta-title">Ready to get started?</h2>
                <p>Create an account to browse live auctions or list your first item.</p>
            </div>
            <div class="marketing-cta__actions">
                <a href="<?= Url::to(['/site/signup']) ?>" class="landing-btn landing-btn--primary">
                    Create an Account
                </a>
                <a href="<?= Url::to(['/site/index']) ?>" class="landing-btn landing-btn--ghost">
                    Back to Home
                </a>
            </div>
        </div>
    </section>
</div>
