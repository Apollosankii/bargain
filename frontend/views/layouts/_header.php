<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var bool $portalLayout */

use common\models\Notification;
use common\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var User|null $user */
$user = Yii::$app->user->identity;
$unread = $user ? Notification::unreadCountFor((int) $user->user_id) : 0;
$portalLayout = $portalLayout ?? false;

$currentRoute = Yii::$app->controller->route ?? '';

$isActive = static function (array $routes) use ($currentRoute): string {
    return in_array($currentRoute, $routes, true) ? ' is-active' : '';
};
?>
<?php if ($portalLayout && $user !== null): ?>
<header class="portal-nav" data-portal-nav>
    <div class="portal-nav__inner">
        <a class="portal-nav__brand" href="<?= Url::to(['/site/index']) ?>">BARGAIN</a>

        <button
            type="button"
            class="portal-nav__toggle"
            data-portal-nav-toggle
            aria-expanded="false"
            aria-controls="portal-nav-panel"
        >
            Menu
        </button>

        <ul class="portal-nav__links" id="portal-nav-panel" data-portal-nav-panel>
            <?php if ($user->isBidder()): ?>
                <li><a class="<?= trim($isActive(['dashboard/bidder'])) ?>" href="<?= Url::to(['/dashboard/bidder']) ?>">Dashboard</a></li>
                <li><a class="<?= trim($isActive(['dashboard/bidder-stats'])) ?>" href="<?= Url::to(['/dashboard/bidder-stats']) ?>">Stats</a></li>
                <li><a class="<?= trim($isActive(['bid/my-bids'])) ?>" href="<?= Url::to(['/bid/my-bids']) ?>">My Bids</a></li>
                <li><a class="<?= trim($isActive(['transaction/index'])) ?>" href="<?= Url::to(['/transaction/index']) ?>">Transactions</a></li>
                <li><a class="<?= trim($isActive(['support/support', 'support/create-ticket', 'support/view-ticket'])) ?>" href="<?= Url::to(['/support/support']) ?>">Support</a></li>
                <li><a class="<?= trim($isActive(['support/complaints', 'support/create-complaint'])) ?>" href="<?= Url::to(['/support/complaints']) ?>">Complaints</a></li>
                <li><a href="<?= Url::to(['/bid/my-bids', 'tab' => 'watchlist']) ?>">Watchlist</a></li>
            <?php elseif ($user->isAuctioneer()): ?>
                <li><a class="<?= trim($isActive(['dashboard/auctioneer'])) ?>" href="<?= Url::to(['/dashboard/auctioneer']) ?>">My Auctions</a></li>
                <li><a class="<?= trim($isActive(['dashboard/auctioneer-stats'])) ?>" href="<?= Url::to(['/dashboard/auctioneer-stats']) ?>">Stats</a></li>
                <li><a class="<?= trim($isActive(['transaction/index'])) ?>" href="<?= Url::to(['/transaction/index']) ?>">Transactions</a></li>
                <li><a class="<?= trim($isActive(['support/support', 'support/create-ticket', 'support/view-ticket'])) ?>" href="<?= Url::to(['/support/support']) ?>">Support</a></li>
                <li><a class="<?= trim($isActive(['support/complaints', 'support/create-complaint'])) ?>" href="<?= Url::to(['/support/complaints']) ?>">Complaints</a></li>
                <li>
                    <a class="portal-nav__cta" href="<?= Url::to(['/auction/create']) ?>">
                        + New Auction
                    </a>
                </li>
            <?php else: ?>
                <li><a class="<?= trim($isActive(['dashboard/admin'])) ?>" href="<?= Url::to(['/dashboard/admin']) ?>">Admin</a></li>
            <?php endif; ?>
            <li>
                <a class="<?= trim($isActive(['notification/index'])) ?>" href="<?= Url::to(['/notification/index']) ?>">
                    Notifications
                    <?php if ($unread > 0): ?>
                        <span class="portal-nav__badge"><?= $unread > 9 ? '9+' : $unread ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <div class="portal-nav__right">
            <?php if (empty($this->params['forceDarkTheme']) || $portalLayout): ?>
                <?= Html::button(
                    '&#127769;',
                    [
                        'id' => 'theme-toggle',
                        'class' => 'btn btn-link nav-link fs-5 p-0',
                        'aria-label' => 'Switch theme',
                        'type' => 'button',
                        'style' => 'color:var(--portal-text-secondary);padding:0.25rem 0.5rem;',
                    ],
                ) ?>
            <?php endif; ?>
            <div class="profile-menu-wrap">
                <button type="button" class="profile-trigger" aria-label="Open account menu" data-profile-trigger>
                    <?php if (!empty($user->profile_photo)): ?>
                        <img src="<?= Html::encode($user->profile_photo) ?>" alt="<?= Html::encode($user->getFullName()) ?>" class="profile-trigger-image" />
                    <?php else: ?>
                        <span class="profile-trigger-badge">
                            <?= Html::encode(strtoupper(substr((string) ($user->first_name ?? ''), 0, 1) . substr((string) ($user->last_name ?? ''), 0, 1))) ?>
                        </span>
                    <?php endif; ?>
                </button>

                <div class="profile-menu" id="profile-menu" hidden>
                    <button type="button" class="profile-menu-link" data-open-account-details onclick="document.getElementById('profile-menu').setAttribute('hidden', 'hidden'); document.getElementById('account-details-modal').removeAttribute('hidden');">
                        Account Details
                    </button>
                    <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'profile-menu-logout']) ?>
                        <?= Html::submitButton('Logout', ['class' => 'profile-menu-logout-button']) ?>
                    <?= Html::endForm() ?>
                </div>
            </div>
        </div>
    </div>
</header>
<?php else: ?>
<nav class="navbar">
    <a class="navbar-brand" href="<?= Url::to(['/site/index']) ?>">BARGAIN</a>

    <ul class="navbar-links">
        <?php if ($user === null): ?>
            <li class="nav-item-mega">
                <a href="<?= Url::to(['/site/how-it-works']) ?>">How It Works</a>
                <div class="nav-mega" role="region" aria-label="How It Works overview">
                    <p class="nav-mega-title">How It Works</p>
                    <ul>
                        <li>Create a bidder or auctioneer account</li>
                        <li>List products or browse live auctions</li>
                        <li>Bid in real time and get outbid alerts</li>
                        <li>Pay securely and collect your win</li>
                    </ul>
                    <a class="nav-mega-cta" href="<?= Url::to(['/site/how-it-works']) ?>">Read the full guide →</a>
                </div>
            </li>
            <li class="nav-item-mega">
                <a href="<?= Url::to(['/site/about']) ?>">About</a>
                <div class="nav-mega" role="region" aria-label="About overview">
                    <p class="nav-mega-title">About Bargain</p>
                    <ul>
                        <li>Real-time online auctions</li>
                        <li>Secure M-Pesa and card payments</li>
                        <li>Transparent bid history and stats</li>
                    </ul>
                    <a class="nav-mega-cta" href="<?= Url::to(['/site/about']) ?>">Learn more →</a>
                </div>
            </li>
            <li><a href="<?= Url::to(['/site/terms']) ?>">T&amp;C</a></li>
            <li><a href="<?= Url::to(['/site/login']) ?>">Sign In</a></li>
        <?php elseif ($user->isBidder()): ?>
            <li><a href="<?= Url::to(['/dashboard/bidder']) ?>">Dashboard</a></li>
            <li><a href="<?= Url::to(['/dashboard/bidder-stats']) ?>">Stats</a></li>
            <li><a href="<?= Url::to(['/bid/my-bids']) ?>">My Bids</a></li>
            <li><a href="<?= Url::to(['/transaction/index']) ?>">Transactions</a></li>
            <li><a href="<?= Url::to(['/bid/my-bids', 'tab' => 'watchlist']) ?>">Watchlist</a></li>
            <li>
                <a href="<?= Url::to(['/notification/index']) ?>" class="notif-badge">
                    Notifications
                    <?php if ($unread > 0): ?>
                        <span class="count"><?= $unread > 9 ? '9+' : $unread ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php elseif ($user->isAuctioneer()): ?>
            <li><a href="<?= Url::to(['/dashboard/auctioneer']) ?>">My Auctions</a></li>
            <li><a href="<?= Url::to(['/dashboard/auctioneer-stats']) ?>">Stats</a></li>
            <li><a href="<?= Url::to(['/transaction/index']) ?>">Transactions</a></li>
            <li><a href="<?= Url::to(['/auction/create']) ?>">New Auction</a></li>
            <li>
                <a href="<?= Url::to(['/notification/index']) ?>" class="notif-badge">
                    Notifications
                    <?php if ($unread > 0): ?>
                        <span class="count"><?= $unread > 9 ? '9+' : $unread ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php else: ?>
            <li><a href="<?= Url::to(['/dashboard/admin']) ?>">Admin</a></li>
        <?php endif; ?>
    </ul>

    <div class="navbar-right">
        <?php if (empty($this->params['forceDarkTheme'])): ?>
            <?= Html::button(
                '&#127769;',
                [
                    'id' => 'theme-toggle',
                    'class' => 'btn btn-link nav-link fs-5 p-0 me-2',
                    'aria-label' => 'Switch to dark mode',
                    'type' => 'button',
                ],
            ) ?>
        <?php endif; ?>
        <?php if ($user === null): ?>
            <a href="<?= Url::to(['/site/signup']) ?>" class="btn btn-primary">Join Today</a>
        <?php else: ?>
            <div class="profile-menu-wrap">
                <button type="button" class="profile-trigger" aria-label="Open account menu" data-profile-trigger>
                    <?php if (!empty($user->profile_photo)): ?>
                        <img src="<?= Html::encode($user->profile_photo) ?>" alt="<?= Html::encode($user->getFullName()) ?>" class="profile-trigger-image" />
                    <?php else: ?>
                        <span class="profile-trigger-badge">
                            <?= Html::encode(strtoupper(substr((string) ($user->first_name ?? ''), 0, 1) . substr((string) ($user->last_name ?? ''), 0, 1))) ?>
                        </span>
                    <?php endif; ?>
                </button>

                <div class="profile-menu" id="profile-menu" hidden>
                    <button type="button" class="profile-menu-link" data-open-account-details onclick="document.getElementById('profile-menu').setAttribute('hidden', 'hidden'); document.getElementById('account-details-modal').removeAttribute('hidden');">
                        Account Details
                    </button>
                    <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'profile-menu-logout']) ?>
                        <?= Html::submitButton('Logout', ['class' => 'profile-menu-logout-button']) ?>
                    <?= Html::endForm() ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>
<?php endif; ?>

<?php if ($user !== null): ?>
    <div class="account-modal-overlay" id="account-details-modal" hidden>
        <div class="account-modal" role="dialog" aria-modal="true" aria-labelledby="account-details-title">
            <div class="account-modal-header">
                <h3 id="account-details-title">Account Details</h3>
                <button type="button" class="account-modal-close" data-close-account-details aria-label="Close account details" onclick="document.getElementById('account-details-modal').setAttribute('hidden', 'hidden');">×</button>
            </div>

            <div class="account-modal-body">
                <div class="account-summary">
                    <?php if (!empty($user->profile_photo)): ?>
                        <img src="<?= Html::encode($user->profile_photo) ?>" alt="<?= Html::encode($user->getFullName()) ?>" class="account-avatar" />
                    <?php else: ?>
                        <div class="account-avatar account-avatar-fallback">
                            <?= Html::encode(strtoupper(substr((string) ($user->first_name ?? ''), 0, 1) . substr((string) ($user->last_name ?? ''), 0, 1))) ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <div class="account-name"><?= Html::encode($user->getFullName()) ?></div>
                        <div class="account-role"><?= Html::encode($user->role) ?></div>
                    </div>
                </div>

                <div class="account-details-grid">
                    <div>
                        <span>First name</span>
                        <strong><?= Html::encode($user->first_name) ?></strong>
                    </div>
                    <div>
                        <span>Last name</span>
                        <strong><?= Html::encode($user->last_name) ?></strong>
                    </div>
                    <div>
                        <span>Email</span>
                        <strong><?= Html::encode($user->email) ?></strong>
                    </div>
                    <div>
                        <span>Phone</span>
                        <strong><?= Html::encode($user->phone) ?></strong>
                    </div>
                </div>

                <?php
                $accountAction = $user->isBidder()
                    ? ['/dashboard/bidder']
                    : ($user->isAuctioneer() ? ['/dashboard/auctioneer'] : ['/dashboard/admin']);
                ?>
                <?= Html::beginForm($accountAction, 'post', ['enctype' => 'multipart/form-data', 'class' => 'account-upload-form', 'onsubmit' => 'setTimeout(() => document.getElementById("account-details-modal").setAttribute("hidden", "hidden"), 100);']) ?>
                    <input type="hidden" name="upload_profile_photo" value="1" />
                    <label class="account-upload-label" for="account-profile-photo">Upload profile photo</label>
                    <?= Html::fileInput('ProfilePhotoForm[photo]', null, ['id' => 'account-profile-photo', 'accept' => 'image/*', 'class' => 'account-upload-input']) ?>
                    <?= Html::submitButton('Save Photo', ['class' => 'btn btn-primary btn-full']) ?>
                <?= Html::endForm() ?>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const trigger = document.querySelector('[data-profile-trigger]');
            const menu = document.getElementById('profile-menu');
            const modal = document.getElementById('account-details-modal');
            const openBtn = document.querySelector('[data-open-account-details]');
            const closeBtn = document.querySelector('[data-close-account-details]');

            if (trigger && menu) {
                trigger.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const isHidden = menu.hasAttribute('hidden');
                    menu.toggleAttribute('hidden', !isHidden);
                });
            }

            if (openBtn && modal) {
                openBtn.addEventListener('click', () => {
                    menu?.setAttribute('hidden', 'hidden');
                    modal.removeAttribute('hidden');
                });
            }

            if (closeBtn && modal) {
                closeBtn.addEventListener('click', () => modal.setAttribute('hidden', 'hidden'));
            }

            if (modal) {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        modal.setAttribute('hidden', 'hidden');
                    }
                });
            }

            document.addEventListener('click', (event) => {
                if (menu && !menu.contains(event.target) && !trigger?.contains(event.target)) {
                    menu.setAttribute('hidden', 'hidden');
                }
            });
        })();
    </script>
<?php endif; ?>
