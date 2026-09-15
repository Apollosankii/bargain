<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Auction;
use common\models\Bid;
use common\models\Payment;
use common\models\SubscriptionPayment;
use common\models\User;
use common\services\MpesaService;
use Throwable;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PaymentController extends Controller
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
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['get', 'post'],
                    'status' => ['get'],
                ],
            ],
        ];
    }

    public function actionCreate(int $auction_id): string|Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        $auction = Auction::findOne($auction_id);
        if ($auction === null) {
            throw new NotFoundHttpException('Auction not found.');
        }

        if (!$this->userWonAuction((int) $user->user_id, $auction_id)) {
            throw new ForbiddenHttpException('Only the winning bidder can pay for this auction.');
        }

        $existing = Payment::findOne([
            'user_id' => $user->user_id,
            'auction_id' => $auction_id,
        ]);

        if ($existing !== null) {
            if ($existing->status === Payment::STATUS_COMPLETED) {
                return $this->redirect(['view', 'id' => $existing->payment_id]);
            }

            if ($existing->status === Payment::STATUS_PENDING) {
                return $this->redirect(['waiting', 'id' => $existing->payment_id]);
            }
        }

        $amount = (int) round((float) $auction->current_bid);
        if ($amount < 1) {
            throw new NotFoundHttpException('Invalid auction amount.');
        }

        $phone = (string) Yii::$app->request->post('phone', $user->phone);

        if (Yii::$app->request->isPost) {
            $normalizedPhone = MpesaService::normalizePhone($phone);
            if ($normalizedPhone === null) {
                Yii::$app->session->setFlash('error', 'Enter a valid Safaricom number (e.g. 0712345678).');

                return $this->render('create', [
                    'auction' => $auction,
                    'amount' => $amount,
                    'phone' => $phone,
                ]);
            }

            $payment = $existing ?? new Payment();
            if ($existing !== null) {
                $payment->prepareForRetry();
            } else {
                $payment->user_id = (int) $user->user_id;
                $payment->auction_id = (int) $auction_id;
            }

            $payment->amount = (string) $amount;
            $payment->method = Payment::METHOD_MPESA;
            $payment->phone = $normalizedPhone;
            $payment->status = Payment::STATUS_PENDING;

            if (!$payment->save()) {
                Yii::$app->session->setFlash('error', 'Could not start payment. Please try again.');

                return $this->render('create', [
                    'auction' => $auction,
                    'amount' => $amount,
                    'phone' => $phone,
                ]);
            }

            try {
                $mpesa = new MpesaService();
                $reference = 'AUC' . $payment->payment_id;
                $stkResponse = $mpesa->stkPush(
                    $normalizedPhone,
                    $amount,
                    $reference,
                    'Bargain ' . mb_substr($auction->title, 0, 8),
                );
            } catch (Throwable $e) {
                $payment->status = Payment::STATUS_FAILED;
                $payment->result_desc = $e->getMessage();
                $payment->save(false);

                Yii::$app->session->setFlash('error', $e->getMessage());

                return $this->render('create', [
                    'auction' => $auction,
                    'amount' => $amount,
                    'phone' => $phone,
                ]);
            }

            $payment->applyStkInitResponse($stkResponse);

            if ($payment->status === Payment::STATUS_FAILED) {
                Yii::$app->session->setFlash('error', $payment->result_desc ?: 'Could not send STK push.');

                return $this->render('create', [
                    'auction' => $auction,
                    'amount' => $amount,
                    'phone' => $phone,
                ]);
            }

            return $this->redirect(['waiting', 'id' => $payment->payment_id]);
        }

        return $this->render('create', [
            'auction' => $auction,
            'amount' => $amount,
            'phone' => $phone,
        ]);
    }

    public function actionWaiting(int $id): string|Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        $payment = $this->findOwnedPayment($id, (int) $user->user_id);
        $payment->markExpiredIfNeeded();

        if ($payment->status === Payment::STATUS_COMPLETED) {
            Yii::$app->session->setFlash('success', 'Payment received. Thank you!');

            return $this->redirect(['view', 'id' => $payment->payment_id]);
        }

        return $this->render('waiting', [
            'payment' => $payment,
        ]);
    }

    public function actionStatus(int $id): Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        $payment = $this->findOwnedPayment($id, (int) $user->user_id);

        if ($payment->status === Payment::STATUS_PENDING) {
            $payment->refreshFromQuery();
            $payment->markExpiredIfNeeded();
            $payment->refresh();
        }

        return $this->asJson([
            'status' => $payment->status,
            'resultDesc' => $payment->result_desc,
            'redirect' => $payment->status === Payment::STATUS_COMPLETED
                ? (string) \yii\helpers\Url::to(['/payment/view', 'id' => $payment->payment_id])
                : null,
        ]);
    }

    public function actionView(int $id): string
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        $payment = $this->findOwnedPayment($id, (int) $user->user_id);

        return $this->render('view', [
            'payment' => $payment,
        ]);
    }

    public function actionMyPayments(): Response
    {
        return $this->redirect(['/transaction/index']);
    }

    private function userWonAuction(int $userId, int $auctionId): bool
    {
        return Bid::find()
            ->where([
                'auction_id' => $auctionId,
                'bidder_id' => $userId,
                'is_winning' => true,
                'status' => Bid::STATUS_WON,
            ])
            ->exists();
    }

    private function findOwnedPayment(int $id, int $userId): Payment
    {
        $payment = Payment::find()
            ->where(['payment_id' => $id, 'user_id' => $userId])
            ->with(['auction'])
            ->one();

        if ($payment === null) {
            throw new NotFoundHttpException('Payment not found.');
        }

        return $payment;
    }
}
