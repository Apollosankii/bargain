<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Auction $auction */
/** @var bool $guest */

use common\models\Auction;
use yii\helpers\Html;
use yii\helpers\Url;

$guest = $guest ?? true;
$viewUrl = $guest
    ? Url::to(['/site/signup'])
    : Url::to(['/auction/view', 'id' => $auction->auction_id]);
?>
<article class="landing-lot">
    <a class="landing-lot__media" href="<?= $viewUrl ?>">
        <?php if ($auction->image_url): ?>
            <img
                src="<?= Html::encode($auction->image_url) ?>"
                alt="<?= Html::encode($auction->title) ?>"
                loading="lazy"
            />
        <?php else: ?>
            <div class="landing-lot__media-placeholder" aria-hidden="true"></div>
        <?php endif; ?>
        <span class="landing-lot__live">LIVE</span>
    </a>

    <div class="landing-lot__body">
        <p class="landing-lot__category"><?= Html::encode((string) ($auction->category_name ?? 'Auction')) ?></p>
        <h3 class="landing-lot__title">
            <a href="<?= $viewUrl ?>"><?= Html::encode($auction->title) ?></a>
        </h3>

        <dl class="landing-lot__stats">
            <div>
                <dt>Current bid</dt>
                <dd><?= Auction::formatKes($auction->current_bid) ?></dd>
            </div>
            <div>
                <dt>Bids</dt>
                <dd><?= (int) $auction->total_bids ?></dd>
            </div>
            <div>
                <dt>Ends</dt>
                <dd><?= Html::encode(Auction::timeRemaining((string) $auction->end_time)) ?></dd>
            </div>
        </dl>

        <a href="<?= $viewUrl ?>" class="landing-lot__action">
            View Auction <span aria-hidden="true">→</span>
        </a>
    </div>
</article>
