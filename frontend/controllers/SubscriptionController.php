<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Subscription;
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

class SubscriptionController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['auctioneer'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'activate' => ['post'],
                    'pay' => ['get', 'post'],
                    'status' => ['get'],
                ],
            ],
        ];
    }

    public function actionChoose(): string|Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        if ($user->hasActiveSubscription()) {
            return $this->redirect(['/dashboard/auctioneer']);
        }

        $pending = SubscriptionPayment::findPendingForUser((int) $user->user_id);
        if ($pending !== null) {
            return $this->redirect(['waiting', 'id' => $pending->subscription_payment_id]);
        }

        $this->layout = 'auth';

        return $this->render('choose', [
            'canStartTrial' => Subscription::canStartTrial((int) $user->user_id),
            'plans' => $this->planCards(),
            'prices' => Yii::$app->params['subscriptionPrices'] ?? [],
        ]);
    }

    public function actionActivate(): Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        if (!$user->isAuctioneer()) {
            throw new ForbiddenHttpException('Only auctioneers can activate a subscription.');
        }

        $plan = (string) Yii::$app->request->post('plan', '');

        if ($plan !== Subscription::PLAN_FREE_TRIAL) {
            return $this->redirect(['pay', 'plan' => $plan]);
        }

        if (!Subscription::canStartTrial((int) $user->user_id)) {
            Yii::$app->session->setFlash('error', 'You have already used your free trial.');

            return $this->redirect(['choose']);
        }

        $subscription = Subscription::startForUser((int) $user->user_id, $plan);

        if ($subscription === null) {
            Yii::$app->session->setFlash('error', 'Could not activate that plan. Please try again.');

            return $this->redirect(['choose']);
        }

        $label = Subscription::planOptions()[$plan] ?? $plan;
        Yii::$app->session->setFlash('success', "{$label} activated. Welcome to your auctioneer dashboard!");

        return $this->redirect(['/dashboard/auctioneer']);
    }

    public function actionPay(string $plan): string|Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        if ($user->hasActiveSubscription()) {
            return $this->redirect(['/dashboard/auctioneer']);
        }

        if (!Subscription::isPaidPlan($plan)) {
            Yii::$app->session->setFlash('error', 'Please choose a valid paid plan.');

            return $this->redirect(['choose']);
        }

        $pending = SubscriptionPayment::findPendingForUser((int) $user->user_id);
        if ($pending !== null) {
            return $this->redirect(['waiting', 'id' => $pending->subscription_payment_id]);
        }

        $amount = SubscriptionPayment::planAmount($plan);
        if ($amount === null || $amount < 1) {
            throw new NotFoundHttpException('Plan price is not configured.');
        }

        $this->layout = 'auth';
        $phone = (string) Yii::$app->request->post('phone', $user->phone);

        if (Yii::$app->request->isPost) {
            $normalizedPhone = MpesaService::normalizePhone($phone);
            if ($normalizedPhone === null) {
                Yii::$app->session->setFlash('error', 'Enter a valid Safaricom number (e.g. 0712345678).');

                return $this->render('pay', [
                    'plan' => $plan,
                    'planLabel' => Subscription::planOptions()[$plan],
                    'amount' => $amount,
                    'phone' => $phone,
                ]);
            }

            $payment = new SubscriptionPayment();
            $payment->user_id = (int) $user->user_id;
            $payment->plan = $plan;
            $payment->amount = (string) $amount;
            $payment->phone = $normalizedPhone;
            $payment->status = SubscriptionPayment::STATUS_PENDING;

            if (!$payment->save()) {
                Yii::$app->session->setFlash('error', 'Could not start payment. Please try again.');

                return $this->redirect(['choose']);
            }

            try {
                $mpesa = new MpesaService();
                $reference = 'SUB' . $payment->subscription_payment_id;
                $stkResponse = $mpesa->stkPush(
                    $normalizedPhone,
                    $amount,
                    $reference,
                    'Bargain ' . Subscription::planOptions()[$plan],
                );
            } catch (Throwable $e) {
                $payment->status = SubscriptionPayment::STATUS_FAILED;
                $payment->result_desc = $e->getMessage();
                $payment->save(false);

                Yii::$app->session->setFlash('error', $e->getMessage());

                return $this->render('pay', [
                    'plan' => $plan,
                    'planLabel' => Subscription::planOptions()[$plan],
                    'amount' => $amount,
                    'phone' => $phone,
                ]);
            }

            $payment->applyStkInitResponse($stkResponse);

            if ($payment->status === SubscriptionPayment::STATUS_FAILED) {
                Yii::$app->session->setFlash('error', $payment->result_desc ?: 'Could not send STK push.');

                return $this->render('pay', [
                    'plan' => $plan,
                    'planLabel' => Subscription::planOptions()[$plan],
                    'amount' => $amount,
                    'phone' => $phone,
                ]);
            }

            return $this->redirect(['waiting', 'id' => $payment->subscription_payment_id]);
        }

        return $this->render('pay', [
            'plan' => $plan,
            'planLabel' => Subscription::planOptions()[$plan],
            'amount' => $amount,
            'phone' => $phone,
        ]);
    }

    public function actionWaiting(int $id): string|Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        $payment = SubscriptionPayment::findOne([
            'subscription_payment_id' => $id,
            'user_id' => $user->user_id,
        ]);

        if ($payment === null) {
            throw new NotFoundHttpException('Payment not found.');
        }

        $payment->markExpiredIfNeeded();

        if ($payment->status === SubscriptionPayment::STATUS_COMPLETED) {
            Yii::$app->session->setFlash('success', 'Payment received. Your subscription is now active!');

            return $this->redirect(['/dashboard/auctioneer']);
        }

        $this->layout = 'auth';

        return $this->render('waiting', [
            'payment' => $payment,
        ]);
    }

    public function actionStatus(int $id): Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        $payment = SubscriptionPayment::findOne([
            'subscription_payment_id' => $id,
            'user_id' => $user->user_id,
        ]);

        if ($payment === null) {
            throw new NotFoundHttpException('Payment not found.');
        }

        if ($payment->status === SubscriptionPayment::STATUS_PENDING) {
            $payment->refreshFromQuery();
            $payment->markExpiredIfNeeded();
            $payment->refresh();
        }

        return $this->asJson([
            'status' => $payment->status,
            'resultDesc' => $payment->result_desc,
            'redirect' => $payment->status === SubscriptionPayment::STATUS_COMPLETED
                ? (string) \yii\helpers\Url::to(['/dashboard/auctioneer'])
                : null,
        ]);
    }

    /**
     * @return array<string, array{title: string, subtitle: string, detail: string, cta: string}>
     */
    private function planCards(): array
    {
        return [
            Subscription::PLAN_FREE_TRIAL => [
                'title' => '7-Day Free Trial',
                'subtitle' => 'Try Bargain risk-free',
                'detail' => 'Full auctioneer access for 7 days. One-time offer.',
                'cta' => 'Start Free Trial',
            ],
            Subscription::PLAN_1_MONTH => [
                'title' => '1 Month',
                'subtitle' => 'Flexible monthly access',
                'detail' => 'Create and manage auctions for 30 days.',
                'cta' => 'Pay with M-Pesa',
            ],
            Subscription::PLAN_6_MONTHS => [
                'title' => '6 Months',
                'subtitle' => 'Best for growing sellers',
                'detail' => 'Half a year of auctioneer tools.',
                'cta' => 'Pay with M-Pesa',
            ],
            Subscription::PLAN_12_MONTHS => [
                'title' => '12 Months',
                'subtitle' => 'Best value',
                'detail' => 'A full year of uninterrupted selling.',
                'cta' => 'Pay with M-Pesa',
            ],
        ];
    }
}
