<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Auction;
use common\models\AuctionInterest;
use common\models\Bid;
use common\models\Category;
use common\models\Notification;
use common\models\User;
use common\services\RbacService;
use common\services\StatsService;
use frontend\models\ProfilePhotoForm;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class DashboardController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionBidder(?int $category_id = null, ?string $search = null): string|Response
    {
        $this->requireRole(User::ROLE_BIDDER);
        AuctionInterest::notifyOpenedAuctionsThrottled();
        Auction::finalizeAllExpired();

        $profileForm = new ProfilePhotoForm();
        $user = Yii::$app->user->identity;

        if (Yii::$app->request->isPost && Yii::$app->request->post('upload_profile_photo') === '1') {
            $profileForm->photo = null;
            if ($profileForm->upload($user)) {
                Yii::$app->session->setFlash('success', 'Your profile photo was updated.');

                return $this->redirect(['dashboard/bidder', 'category_id' => $category_id, 'search' => $search]);
            }
            Yii::$app->session->setFlash('error', $profileForm->getFirstError('photo') ?: 'Could not upload the profile photo.');
        }

        $categories = Category::listWithActiveCounts();
        $auctions = Auction::findActive($category_id, $search);
        $unread = Notification::unreadCountFor((int) Yii::$app->user->id);

        $leadingAuctionIds = [];
        if ($auctions !== []) {
            $auctionIds = array_map(static fn (Auction $a) => (int) $a->auction_id, $auctions);
            $leadingAuctionIds = Bid::find()
                ->select(['auction_id'])
                ->where([
                    'bidder_id' => (int) Yii::$app->user->id,
                    'is_winning' => true,
                    'auction_id' => $auctionIds,
                ])
                ->column();
            $leadingAuctionIds = array_map('intval', $leadingAuctionIds);
        }

        return $this->render('bidder', [
            'categories' => $categories,
            'auctions' => $auctions,
            'categoryId' => $category_id,
            'search' => $search ?? '',
            'unread' => $unread,
            'leadingAuctionIds' => $leadingAuctionIds,
            'profileForm' => $profileForm,
        ]);
    }

    public function actionAuctioneer(): string|Response
    {
        $this->requireRole(User::ROLE_AUCTIONEER);

        /** @var User $user */
        $user = Yii::$app->user->identity;

        if ($user->requiresSubscriptionGate()) {
            return $this->redirect(['/subscription/choose']);
        }

        $profileForm = new ProfilePhotoForm();

        if (Yii::$app->request->isPost && Yii::$app->request->post('upload_profile_photo') === '1') {
            $profileForm->photo = null;
            if ($profileForm->upload($user)) {
                Yii::$app->session->setFlash('success', 'Your profile photo was updated.');

                return $this->redirect(['/dashboard/auctioneer']);
            }
            Yii::$app->session->setFlash('error', $profileForm->getFirstError('photo') ?: 'Could not upload the profile photo.');
        }

        Auction::finalizeAllExpired();

        $grouped = Auction::findGroupedForAuctioneer((int) $user->user_id);
        $unread = Notification::unreadCountFor((int) $user->user_id);

        $totalBids = 0;
        foreach ($grouped['active'] as $a) {
            $totalBids += (int) $a->total_bids;
        }

        $earnings = 0.0;
        foreach ($grouped['closed'] as $a) {
            $earnings += (float) ($a->final_price ?? 0);
        }

        return $this->render('auctioneer', [
            'active' => $grouped['active'],
            'timedOut' => $grouped['timedOut'],
            'closed' => $grouped['closed'],
            'cancelled' => $grouped['cancelled'],
            'statActive' => count($grouped['active']),
            'statBids' => $totalBids,
            'statEarnings' => $earnings,
            'unread' => $unread,
            'profileForm' => $profileForm,
        ]);
    }

    public function actionAdmin(): string
    {
        $this->requireRole(User::ROLE_ADMIN);

        return $this->render('admin');
    }

    public function actionAuctioneerStats(): string|Response
    {
        $this->requireRole(User::ROLE_AUCTIONEER);

        /** @var User $user */
        $user = Yii::$app->user->identity;

        if ($user->requiresSubscriptionGate()) {
            return $this->redirect(['/subscription/choose']);
        }

        return $this->render('auctioneer-stats', [
            'stats' => StatsService::forAuctioneer(
                (int) $user->user_id,
                (string) Yii::$app->request->get('range', '30'),
            ),
        ]);
    }

    public function actionBidderStats(): string
    {
        $this->requireRole(User::ROLE_BIDDER);

        /** @var User $user */
        $user = Yii::$app->user->identity;

        return $this->render('bidder-stats', [
            'stats' => StatsService::forBidder(
                (int) $user->user_id,
                (string) Yii::$app->request->get('range', '30'),
            ),
        ]);
    }

    private function requireRole(string $role): void
    {
        RbacService::requireRole(RbacService::roleFromUserConstant($role));
    }
}
