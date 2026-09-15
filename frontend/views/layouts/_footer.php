<?php

declare(strict_types=1);

use yii\helpers\Url;
?>
<footer class="site-footer">
    <div class="site-footer-inner">
        <span>&copy; <?= date('Y') ?> Bargain — Smart Bargaining System.</span>
        <nav class="site-footer-links" aria-label="Footer">
            <a href="<?= Url::to(['/site/about']) ?>">About</a>
            <a href="<?= Url::to(['/site/how-it-works']) ?>">How It Works</a>
            <a href="<?= Url::to(['/site/terms']) ?>">T&amp;C</a>
        </nav>
    </div>
</footer>
