<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Auction;
use common\models\Payment;
use common\models\Subscription;
use common\models\SubscriptionPayment;
use common\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

class TransactionController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['auctioneer', 'bidder'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        if ($user->isBidder()) {
            return $this->render('bidder', [
                'transactions' => $this->bidderTransactions((int) $user->user_id),
            ]);
        }

        if ($user->isAuctioneer()) {
            return $this->render('auctioneer', [
                'incoming' => $this->auctioneerIncoming((int) $user->user_id),
                'subscriptions' => $this->auctioneerSubscriptions((int) $user->user_id),
            ]);
        }

        throw new ForbiddenHttpException('Transactions are only available to bidders and auctioneers.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function bidderTransactions(int $userId): array
    {
        $payments = Payment::find()
            ->where(['user_id' => $userId])
            ->with(['auction'])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $rows = [];
        foreach ($payments as $payment) {
            $rows[] = [
                'id' => $payment->payment_id,
                'date' => $payment->created_at,
                'type' => 'Auction payment',
                'description' => $payment->auction?->title ?? ('Auction #' . $payment->auction_id),
                'amount' => $payment->amount,
                'method' => Payment::methodOptions()[$payment->method] ?? $payment->method,
                'status' => $payment->status,
                'receipt' => $payment->mpesa_receipt,
                'phone' => $payment->phone,
                'viewUrl' => ['/payment/view', 'id' => $payment->payment_id],
                'auctionUrl' => ['/auction/view', 'id' => $payment->auction_id],
            ];
        }

        return $rows;
    }

    /**
     * Payments received on this auctioneer's auctions.
     *
     * @return list<array<string, mixed>>
     */
    private function auctioneerIncoming(int $auctioneerId): array
    {
        $payments = Payment::find()
            ->alias('p')
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = p.auction_id')
            ->where(['a.auctioneer_id' => $auctioneerId])
            ->with(['auction', 'user'])
            ->orderBy(['p.created_at' => SORT_DESC])
            ->all();

        $rows = [];
        foreach ($payments as $payment) {
            $bidder = $payment->user;
            $rows[] = [
                'id' => $payment->payment_id,
                'date' => $payment->created_at,
                'type' => 'Auction sale',
                'description' => $payment->auction?->title ?? ('Auction #' . $payment->auction_id),
                'counterparty' => $bidder
                    ? trim($bidder->first_name . ' ' . $bidder->last_name)
                    : ('User #' . $payment->user_id),
                'amount' => $payment->amount,
                'method' => Payment::methodOptions()[$payment->method] ?? $payment->method,
                'status' => $payment->status,
                'receipt' => $payment->mpesa_receipt,
                'auctionUrl' => ['/auction/view', 'id' => $payment->auction_id],
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function auctioneerSubscriptions(int $userId): array
    {
        $payments = SubscriptionPayment::find()
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $rows = [];
        foreach ($payments as $payment) {
            $planLabel = Subscription::planOptions()[$payment->plan] ?? $payment->plan;
            $rows[] = [
                'id' => $payment->subscription_payment_id,
                'date' => $payment->created_at,
                'type' => 'Subscription',
                'description' => $planLabel,
                'amount' => $payment->amount,
                'method' => 'M-Pesa',
                'status' => $payment->status,
                'receipt' => $payment->mpesa_receipt,
                'phone' => $payment->phone,
            ];
        }

        return $rows;
    }
}
