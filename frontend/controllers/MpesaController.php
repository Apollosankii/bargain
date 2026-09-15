<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Payment;
use common\models\SubscriptionPayment;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class MpesaController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'stk-callback' => ['post'],
                ],
            ],
        ];
    }

    public function actionStkCallback(): Response
    {
        $raw = Yii::$app->request->getRawBody();
        Yii::info('M-Pesa STK callback received.', __METHOD__);

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            return $this->asJson([
                'ResultCode' => 1,
                'ResultDesc' => 'Invalid JSON payload',
            ]);
        }

        try {
            SubscriptionPayment::handleStkCallback($payload);
            Payment::handleStkCallback($payload);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
        }

        return $this->asJson([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }
}
