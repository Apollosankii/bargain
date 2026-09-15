<?php

declare(strict_types=1);

use yii\helpers\Url;
?>
<footer class="landing-footer">
    <div class="landing-footer__inner">
        <div class="landing-footer__brand">
            <a class="landing-footer__logo" href="<?= Url::to(['/site/index']) ?>">BARGAIN</a>
            <p class="landing-footer__desc">
                Real-time online auctions connecting buyers and sellers across Kenya.
            </p>
        </div>

        <nav class="landing-footer__col" aria-label="Product">
            <p class="landing-footer__label">Product</p>
            <a href="<?= Url::to(['/site/how-it-works']) ?>">How It Works</a>
            <a href="<?= Url::to(['/site/index', '#' => 'live-auctions']) ?>">Auctions</a>
            <a href="<?= Url::to(['/site/about']) ?>">About</a>
        </nav>

        <nav class="landing-footer__col" aria-label="Legal">
            <p class="landing-footer__label">Legal</p>
            <a href="<?= Url::to(['/site/terms']) ?>">Terms &amp; Conditions</a>
            <a href="<?= Url::to(['/site/contact']) ?>">Contact</a>
        </nav>

        <div class="landing-footer__meta">
            <p>&copy; <?= date('Y') ?> Bargain</p>
        </div>
    </div>
</footer>
