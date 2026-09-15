<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Bid;
use common\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

class BidController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['bidder'],
                    ],
                ],
            ],
        ];
    }

    public function actionMyBids(string $tab = 'bids'): string
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        $bids = Bid::findForBidder((int) $user->user_id);
        $watchlist = Bid::findWatchlist((int) $user->user_id);

        return $this->render('my-bids', [
            'bids' => $bids,
            'watchlist' => $watchlist,
            'tab' => $tab === 'watchlist' ? 'watchlist' : 'bids',
        ]);
    }
}
