<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Auction $auction */
/** @var common\models\Bid|null $winningBid */
/** @var common\models\Bid[] $bids */
/** @var frontend\models\BidForm $bidForm */

/** @var bool $isInterested */
/** @var bool $canExtend */
/** @var list<string> $gallery */

use common\models\Auction;
use common\models\User;
use common\models\Payment;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

$this->title = $auction->title . ' — Bargain';
/** @var User $user */
$user = Yii::$app->user->identity;
$isLeading = $user->isBidder()
    && $winningBid
    && (int) $winningBid->bidder_id === (int) $user->user_id
    && in_array($winningBid->status, [
        \common\models\Bid::STATUS_WINNING,
        \common\models\Bid::STATUS_WON,
    ], true);
$canBid = $user->isBidder() && $auction->isAcceptingBids() && !$isLeading;
$isScheduled = $user->isBidder() && $auction->isScheduled();
$isWinner = $user->isBidder()
    && $winningBid
    && (int) $winningBid->bidder_id === (int) $user->user_id
    && $winningBid->status === \common\models\Bid::STATUS_WON;
$winnerPayment = $isWinner
    ? Payment::findOne(['user_id' => $user->user_id, 'auction_id' => $auction->auction_id])
    : null;
$countdownTs = ($isScheduled ? strtotime((string) $auction->start_time) : strtotime((string) $auction->end_time)) * 1000;
$shouldReloadWhenEnded = $auction->status === Auction::STATUS_ACTIVE && !$isScheduled;
$statusClass = match ($auction->status) {
    Auction::STATUS_ACTIVE => 'badge-success',
    Auction::STATUS_CLOSED => 'badge-muted',
    default => 'badge-error',
};
$gallery = $gallery ?? $auction->getGalleryUrls();
$canExtend = $canExtend ?? false;
?>
<div class="auction-layout">
    <div>
        <div class="auction-gallery">
            <div class="auction-image<?= !empty($gallery) ? ' is-zoomable' : '' ?>" id="auction-image"<?= !empty($gallery) ? ' role="button" tabindex="0" aria-label="Zoom image"' : '' ?>>
                <?php if (!empty($gallery)): ?>
                    <img
                        id="auction-main-image"
                        src="<?= Html::encode($gallery[0]) ?>"
                        alt="<?= Html::encode($auction->title) ?>"
                        class="auction-main-photo"
                        data-index="0"
                    >
                <?php else: ?>
                    <div class="auction-image-placeholder">[image]</div>
                <?php endif; ?>
            </div>
            <?php if (count($gallery) > 1): ?>
                <div class="auction-thumbs" id="auction-thumbs">
                    <?php foreach ($gallery as $i => $url): ?>
                        <button
                            type="button"
                            class="auction-thumb<?= $i === 0 ? ' is-active' : '' ?>"
                            data-index="<?= $i ?>"
                            data-src="<?= Html::encode($url) ?>"
                        >
                            <img src="<?= Html::encode($url) ?>" alt="Photo <?= $i + 1 ?>">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-top:1.5rem;">
            <div style="display:flex; align-items:center; gap:0.8rem; margin-bottom:0.5rem;">
                <span class="badge badge-muted"><?= Html::encode($auction->category_name ?? $auction->category->name ?? '') ?></span>
                <span class="badge <?= $statusClass ?>"><?= Html::encode($auction->status) ?></span>
            </div>
            <h1 style="font-size:1.5rem; margin-bottom:0.8rem;"><?= Html::encode($auction->title) ?></h1>
            <p style="color:var(--text-secondary); font-size:0.9rem; line-height:1.7;">
                <?= nl2br(Html::encode($auction->description ?: 'No description provided.')) ?>
            </p>

            <div style="margin-top:1.5rem; display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="stat-card">
                    <div class="stat-card-label">Starting Bid</div>
                    <div class="stat-card-value" style="font-size:1.2rem;"><?= Auction::formatKes($auction->starting_bid) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">Total Bids</div>
                    <div class="stat-card-value" style="font-size:1.2rem;"><?= count($bids) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">Start Time</div>
                    <div class="stat-card-value" style="font-size:0.9rem;"><?= Html::encode(date('M j, Y H:i', strtotime((string) $auction->start_time))) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">End Time</div>
                    <div class="stat-card-value" style="font-size:0.9rem;"><?= Html::encode(date('M j, Y H:i', strtotime((string) $auction->end_time))) ?></div>
                </div>
            </div>
        </div>

        <div class="bid-history">
            <div class="section-title" style="margin-top:2rem;">Bid History</div>
            <?php if (empty($bids)): ?>
                <p style="color:var(--text-muted); font-size:0.85rem;">No bids yet. Be the first!</p>
            <?php else: ?>
                <?php foreach ($bids as $i => $bid): ?>
                    <?php
                    $isMine = (int) $bid->bidder_id === (int) $user->user_id;
                    $bidderLabel = $isMine
                        ? 'You'
                        : trim(($bid->first_name ?? '') . ' ' . mb_substr((string) ($bid->last_name ?? ''), 0, 1) . '.');
                    ?>
                    <div class="bid-history-item">
                        <div>
                            <strong><?= Html::encode($bidderLabel) ?></strong>
                            <?php if ($i === 0 && $bid->is_winning): ?>
                                <span class="badge badge-success" style="margin-left:0.4rem;">Winning</span>
                            <?php endif; ?>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:700;"><?= Auction::formatKes($bid->bid_amount) ?></div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">
                                <?= Html::encode(date('M j, H:i', strtotime((string) $bid->bid_time))) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="bid-panel">
            <div class="countdown">
                <div class="countdown-label"><?= $isScheduled ? 'Starts In' : 'Time Remaining' ?></div>
                <div class="countdown-time" id="countdown" aria-live="polite">—</div>
            </div>

            <div class="price-display">
                <span class="price-label">Current Bid</span>
                <span class="price-value"><?= Auction::formatKes($auction->current_bid) ?></span>
            </div>

            <div style="font-size:0.82rem; color:var(--text-secondary); margin-bottom:1.2rem;">
                Leading:
                <span style="color:var(--text-primary);">
                    <?php if ($winningBid && $winningBid->bidder): ?>
                        <?= (int) $winningBid->bidder_id === (int) $user->user_id
                            ? 'You'
                            : Html::encode($winningBid->bidder->first_name . ' ' . $winningBid->bidder->last_name) ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </span>
            </div>

            <?php if ($canBid): ?>
                <?php $form = ActiveForm::begin([
                    'id' => 'bid-form',
                    'fieldConfig' => [
                        'template' => "{label}\n{input}\n{error}",
                        'options' => ['class' => 'form-group'],
                    ],
                ]); ?>
                    <?= $form->field($bidForm, 'auction_id')->hiddenInput()->label(false) ?>
                    <?= $form->field($bidForm, 'bid_amount')->textInput([
                        'type' => 'number',
                        'step' => '100',
                        'min' => (float) $auction->current_bid + 1,
                        'placeholder' => 'Min: ' . Auction::formatKes((float) $auction->current_bid + 1),
                    ]) ?>
                    <?= Html::submitButton('Place Bid', ['class' => 'btn btn-primary btn-full']) ?>
                    <p style="font-size:0.75rem; color:var(--text-muted); text-align:center; margin-top:0.8rem;">
                        Your bid must be higher than the current bid
                    </p>
                <?php ActiveForm::end(); ?>
            <?php elseif ($isLeading && $auction->isAcceptingBids()): ?>
                <div style="text-align:center;">
                    <p style="color:var(--text-secondary); font-size:0.88rem; margin-bottom:0.5rem;">
                        You are the leading bidder.
                    </p>
                    <p style="font-size:0.75rem; color:var(--text-muted);">
                        Place Bid will appear again only if someone outbids you.
                    </p>
                </div>
            <?php elseif ($isScheduled): ?>
                <div style="text-align:center;">
                    <p style="color:var(--text-secondary); font-size:0.88rem; margin-bottom:1rem;">
                        This auction has not started yet.
                        Opens <?= Html::encode(date('M j, Y g:i A', strtotime((string) $auction->start_time))) ?>.
                    </p>
                    <?= Html::beginForm(['/auction/notify-interest', 'id' => $auction->auction_id], 'post') ?>
                        <?= Html::submitButton(
                            $isInterested ? 'Remove notification' : 'Notify me when it opens',
                            [
                                'class' => $isInterested ? 'btn btn-outline btn-full' : 'btn btn-primary btn-full',
                            ],
                        ) ?>
                    <?= Html::endForm() ?>
                    <?php if ($isInterested): ?>
                        <p style="font-size:0.75rem; color:var(--text-muted); margin-top:0.8rem;">
                            You will get a notification when bidding opens.
                        </p>
                    <?php endif; ?>
                </div>
            <?php elseif ($canExtend): ?>
                <div style="text-align:center;">
                    <p style="color:var(--text-secondary); font-size:0.88rem; margin-bottom:1rem;">
                        This auction timed out with no bids. Extend the end time to reopen bidding.
                    </p>
                    <?= Html::beginForm(['/auction/extend', 'id' => $auction->auction_id], 'post') ?>
                        <div class="form-group" style="text-align:left;">
                            <label class="form-label">Extend by</label>
                            <?= Html::dropDownList('extend_preset', '3d', [
                                '1d' => '1 day',
                                '3d' => '3 days',
                                '7d' => '7 days',
                                'custom' => 'Custom date/time',
                            ], [
                                'class' => 'form-control',
                                'id' => 'extend-preset',
                            ]) ?>
                        </div>
                        <div class="form-group" id="extend-custom-wrap" style="display:none; text-align:left;">
                            <label class="form-label" for="extend-end-time">New end time</label>
                            <?= Html::input('datetime-local', 'end_time', date('Y-m-d\TH:i', strtotime('+3 days')), [
                                'class' => 'form-control',
                                'id' => 'extend-end-time',
                            ]) ?>
                        </div>
                        <?= Html::submitButton('Extend auction', ['class' => 'btn btn-primary btn-full']) ?>
                    <?= Html::endForm() ?>
                </div>
            <?php elseif ($isWinner): ?>
                <div style="text-align:center;">
                    <div style="font-size:2rem; margin-bottom:0.5rem;">🏆</div>
                    <p style="color:var(--text-secondary); font-size:0.88rem;">
                        You won this auction. Complete payment to proceed.
                    </p>
                    <?php if ($winnerPayment === null || $winnerPayment->status === Payment::STATUS_FAILED): ?>
                        <a class="btn btn-primary btn-full" href="<?= Url::to(['/payment/create', 'auction_id' => $auction->auction_id]) ?>">
                            Pay with M-Pesa
                        </a>
                    <?php elseif ($winnerPayment->status === Payment::STATUS_PENDING): ?>
                        <a class="btn btn-primary btn-full" href="<?= Url::to(['/payment/waiting', 'id' => $winnerPayment->payment_id]) ?>">
                            Complete M-Pesa payment
                        </a>
                    <?php else: ?>
                        <a class="btn btn-outline btn-full" href="<?= Url::to(['/payment/view', 'id' => $winnerPayment->payment_id]) ?>">
                            View payment receipt
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="text-align:center;">
                    <div style="font-size:2rem; margin-bottom:0.5rem;">🔒</div>
                    <p style="color:var(--text-secondary); font-size:0.88rem;">
                        <?php if (!$user->isBidder()): ?>
                            Only bidders can place bids.
                        <?php else: ?>
                            This auction is not accepting bids.
                        <?php endif; ?>
                    </p>
                    <?php if ($user->isBidder()): ?>
                        <a href="<?= Url::to(['/dashboard/bidder']) ?>" class="btn btn-outline btn-sm" style="margin-top:0.8rem;">
                            ← Back to browse
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($gallery)): ?>
<div class="image-lightbox" id="image-lightbox" aria-hidden="true">
    <button type="button" class="image-lightbox-close" id="lightbox-close" aria-label="Close">&times;</button>
    <?php if (count($gallery) > 1): ?>
        <button type="button" class="image-lightbox-nav prev" id="lightbox-prev" aria-label="Previous">‹</button>
        <button type="button" class="image-lightbox-nav next" id="lightbox-next" aria-label="Next">›</button>
    <?php endif; ?>
    <img id="lightbox-image" src="<?= Html::encode($gallery[0]) ?>" alt="<?= Html::encode($auction->title) ?>">
</div>
<?php endif; ?>

<?php
$galleryJs = Json::htmlEncode(array_values($gallery));
$countdownJs = (int) $countdownTs;
$reloadJs = $shouldReloadWhenEnded ? 'true' : 'false';
$this->registerJs(<<<JS
(function () {
    var end = {$countdownJs};
    var shouldReload = {$reloadJs};
    var el = document.getElementById('countdown');
    if (el) {
        function unit(value, label) {
            return '<span class="countdown-unit">' +
                '<span class="countdown-value">' + value + '</span>' +
                '<span class="countdown-unit-label">' + label + '</span>' +
                '</span>';
        }
        function tick() {
            var diff = end - Date.now();
            if (diff <= 0) {
                el.textContent = 'Ended';
                if (shouldReload) {
                    window.setTimeout(function () { window.location.reload(); }, 800);
                }
                return;
            }
            var d = Math.floor(diff / 86400000);
            var h = Math.floor((diff % 86400000) / 3600000);
            var m = Math.floor((diff % 3600000) / 60000);
            var s = Math.floor((diff % 60000) / 1000);
            var parts = [];
            if (d > 0) {
                parts.push(unit(d, d === 1 ? 'day' : 'days'));
            }
            if (d > 0 || h > 0) {
                parts.push(unit(h, h === 1 ? 'hr' : 'hrs'));
            }
            parts.push(unit(m, 'min'));
            parts.push(unit(s, 'sec'));
            el.innerHTML = parts.join('');
            setTimeout(tick, 250);
        }
        tick();
    }

    var preset = document.getElementById('extend-preset');
    var customWrap = document.getElementById('extend-custom-wrap');
    if (preset && customWrap) {
        preset.addEventListener('change', function () {
            customWrap.style.display = preset.value === 'custom' ? 'block' : 'none';
        });
    }

    var gallery = {$galleryJs};
    if (!gallery.length) {
        return;
    }

    var frame = document.getElementById('auction-image');
    var main = document.getElementById('auction-main-image');
    var thumbs = document.getElementById('auction-thumbs');
    var lightbox = document.getElementById('image-lightbox');
    var lightboxImg = document.getElementById('lightbox-image');
    var index = 0;

    function setMain(i) {
        index = ((i % gallery.length) + gallery.length) % gallery.length;
        if (main) {
            main.src = gallery[index];
            main.dataset.index = String(index);
        }
        if (lightboxImg) {
            lightboxImg.src = gallery[index];
        }
        if (thumbs) {
            thumbs.querySelectorAll('.auction-thumb').forEach(function (btn) {
                btn.classList.toggle('is-active', Number(btn.dataset.index) === index);
            });
        }
    }

    function openLightbox() {
        if (!lightbox) return;
        setMain(index);
        lightbox.classList.add('is-open');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        if (!lightbox) return;
        lightbox.classList.remove('is-open');
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (frame) {
        frame.addEventListener('click', function (e) {
            if (e.target.closest('.auction-thumb')) return;
            openLightbox();
        });
        frame.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openLightbox();
            }
        });
    }

    if (thumbs) {
        thumbs.addEventListener('click', function (e) {
            var btn = e.target.closest('.auction-thumb');
            if (!btn) return;
            e.stopPropagation();
            setMain(Number(btn.dataset.index));
        });
    }

    var closeBtn = document.getElementById('lightbox-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeLightbox();
        });
    }

    if (lightbox) {
        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox) closeLightbox();
        });
    }

    var prev = document.getElementById('lightbox-prev');
    var next = document.getElementById('lightbox-next');
    if (prev) {
        prev.addEventListener('click', function (e) {
            e.stopPropagation();
            setMain(index - 1);
        });
    }
    if (next) {
        next.addEventListener('click', function (e) {
            e.stopPropagation();
            setMain(index + 1);
        });
    }

    document.addEventListener('keydown', function (e) {
        if (!lightbox || !lightbox.classList.contains('is-open')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') setMain(index - 1);
        if (e.key === 'ArrowRight') setMain(index + 1);
    });
})();
JS);
?>
