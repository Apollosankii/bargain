<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Url;
?>
<header class="landing-nav">
    <div class="landing-nav__inner">
        <a class="landing-nav__brand" href="<?= Url::to(['/site/index']) ?>">BARGAIN</a>

        <button
            type="button"
            class="landing-nav__toggle"
            data-landing-nav-toggle
            aria-expanded="false"
            aria-controls="landing-nav-panel"
        >
            Menu
        </button>

        <nav class="landing-nav__links" id="landing-nav-panel" data-landing-nav-panel aria-label="Primary">
            <a href="<?= Url::to(['/site/how-it-works']) ?>">How It Works</a>
            <a class="is-primary" href="<?= Url::to(['/site/index', '#' => 'live-auctions']) ?>">Auctions</a>
            <a href="<?= Url::to(['/site/about']) ?>">About</a>
            <a href="<?= Url::to(['/site/terms']) ?>">T&amp;C</a>
            <div class="landing-nav__mobile-auth">
                <a href="<?= Url::to(['/site/login']) ?>" class="landing-nav__signin">Sign In</a>
                <a href="<?= Url::to(['/site/signup']) ?>" class="landing-btn landing-btn--primary landing-btn--sm">Join Bargain</a>
            </div>
        </nav>

        <div class="landing-nav__actions">
            <a href="<?= Url::to(['/site/login']) ?>" class="landing-nav__signin">Sign In</a>
            <a href="<?= Url::to(['/site/signup']) ?>" class="landing-btn landing-btn--primary landing-btn--sm landing-btn--nav">Join Bargain</a>
        </div>
    </div>
</header>
