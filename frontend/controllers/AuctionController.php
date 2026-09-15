<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Auction;
use common\models\AuctionInterest;
use common\models\Category;
use common\models\User;
use frontend\models\AuctionForm;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AuctionController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['view'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['create', 'end', 'extend'],
                        'allow' => true,
                        'roles' => ['auctioneer'],
                    ],
                    [
                        'actions' => ['notify-interest'],
                        'allow' => true,
                        'roles' => ['bidder'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'end' => ['post'],
                    'extend' => ['post'],
                    'notify-interest' => ['post'],
                ],
            ],
        ];
    }

    public function actionView(int $id): string|Response
    {
        AuctionInterest::notifyOpenedAuctionsThrottled();
        Auction::finalizeAllExpired();

        $auction = Auction::find()
            ->alias('a')
            ->select(['a.*', 'category_name' => 'c.name'])
            ->innerJoin(['c' => Category::tableName()], 'a.category_id = c.category_id')
            ->where(['a.auction_id' => $id])
            ->one();

        if ($auction === null) {
            throw new NotFoundHttpException('Auction not found.');
        }

        $winningBid = $auction->winningBid;
        if ($winningBid !== null) {
            $winningBid->populateRelation('bidder', $winningBid->bidder);
        }

        $bids = \common\models\Bid::findByAuction($id);
        $bidForm = new \frontend\models\BidForm();
        $bidForm->auction_id = $id;

        if (
            Yii::$app->request->isPost
            && $bidForm->load(Yii::$app->request->post())
        ) {
            /** @var User $user */
            $user = Yii::$app->user->identity;
            $result = $bidForm->place($user);

            if ($result['ok']) {
                Yii::$app->session->setFlash('success', $result['message']);

                return $this->refresh();
            }

            Yii::$app->session->setFlash('error', $result['message']);
        }

        /** @var User $user */
        $user = Yii::$app->user->identity;
        $isInterested = $user->isBidder()
            && AuctionInterest::isInterested((int) $user->user_id, (int) $auction->auction_id);

        $canExtend = $user->isAuctioneer()
            && (int) $auction->auctioneer_id === (int) $user->user_id
            && $auction->canExtend();

        return $this->render('view', [
            'auction' => $auction,
            'winningBid' => $winningBid,
            'bids' => $bids,
            'bidForm' => $bidForm,
            'isInterested' => $isInterested,
            'canExtend' => $canExtend,
            'gallery' => $auction->getGalleryUrls(),
        ]);
    }

    public function actionNotifyInterest(int $id): Response
    {
        $auction = Auction::findOne($id);
        if ($auction === null) {
            throw new NotFoundHttpException('Auction not found.');
        }

        /** @var User $user */
        $user = Yii::$app->user->identity;
        $result = AuctionInterest::toggle((int) $user->user_id, $auction);

        Yii::$app->session->setFlash(
            $result['ok'] ? 'success' : 'error',
            $result['message'],
        );

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionCreate(): string|Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        if ($user->requiresSubscriptionGate()) {
            return $this->redirect(['/subscription/choose']);
        }

        $model = new AuctionForm();
        $model->start_time = date('Y-m-d\TH:i');
        $model->end_time = date('Y-m-d\TH:i', strtotime('+3 days'));

        if ($model->load(Yii::$app->request->post())) {
            $auction = $model->create($user);

            if ($auction !== null) {
                Yii::$app->session->setFlash('success', 'Auction created successfully.');

                return $this->redirect(['/dashboard/auctioneer']);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'categories' => Category::dropdownOptions(),
        ]);
    }

    public function actionEnd(int $id): Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        if ($user->requiresSubscriptionGate()) {
            return $this->redirect(['/subscription/choose']);
        }

        $auction = Auction::findOne($id);

        if ($auction === null) {
            throw new NotFoundHttpException('Auction not found.');
        }

        if ((int) $auction->auctioneer_id !== (int) $user->user_id) {
            throw new ForbiddenHttpException('You do not own this auction.');
        }

        if ($auction->endEarly()) {
            Yii::$app->session->setFlash('success', 'Auction ended successfully.');
        } else {
            Yii::$app->session->setFlash('error', 'Could not end this auction.');
        }

        return $this->redirect(['/dashboard/auctioneer']);
    }

    public function actionExtend(int $id): Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        if ($user->requiresSubscriptionGate()) {
            return $this->redirect(['/subscription/choose']);
        }

        $auction = Auction::findOne($id);
        if ($auction === null) {
            throw new NotFoundHttpException('Auction not found.');
        }

        if ((int) $auction->auctioneer_id !== (int) $user->user_id) {
            throw new ForbiddenHttpException('You do not own this auction.');
        }

        if (!$auction->canExtend()) {
            Yii::$app->session->setFlash('error', 'Only timed-out auctions with no bids can be extended.');

            return $this->redirect(['view', 'id' => $id]);
        }

        $preset = (string) Yii::$app->request->post('extend_preset', '');
        $custom = (string) Yii::$app->request->post('end_time', '');

        $newEnd = match ($preset) {
            '1d' => date('Y-m-d H:i:s', strtotime('+1 day')),
            '3d' => date('Y-m-d H:i:s', strtotime('+3 days')),
            '7d' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'custom' => $custom !== '' ? date('Y-m-d H:i:s', (int) strtotime($custom)) : '',
            default => '',
        };

        if ($newEnd === '' || strtotime($newEnd) <= time()) {
            Yii::$app->session->setFlash('error', 'Choose a valid future end time.');

            return $this->redirect(['view', 'id' => $id]);
        }

        if ($auction->extendUntil($newEnd)) {
            Yii::$app->session->setFlash('success', 'Auction extended. Bidding is open again.');
        } else {
            Yii::$app->session->setFlash('error', 'Could not extend this auction.');
        }

        return $this->redirect(['view', 'id' => $id]);
    }
}
