<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Payment;
use common\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
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
                        'roles' => ['admin'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'update-status' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(string $status = Payment::STATUS_PENDING): string
    {
        $payments = Payment::find()
            ->with(['user', 'auction'])
            ->andWhere(['status' => $status])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('index', [
            'payments' => $payments,
            'status' => $status,
        ]);
    }

    public function actionUpdateStatus(int $id): Response
    {
        $payment = Payment::findOne($id);
        if ($payment === null) {
            throw new NotFoundHttpException('Payment not found.');
        }

        $newStatus = (string) Yii::$app->request->post('status', '');
        $allowed = [
            Payment::STATUS_PENDING,
            Payment::STATUS_COMPLETED,
            Payment::STATUS_FAILED,
            Payment::STATUS_REFUNDED,
        ];

        if (!in_array($newStatus, $allowed, true)) {
            Yii::$app->session->setFlash('error', 'Invalid status.');
            return $this->redirect(['index']);
        }

        $payment->status = $newStatus;
        if ($payment->save(false, ['status', 'updated_at'])) {
            Yii::$app->session->setFlash('success', 'Payment status updated.');
        } else {
            Yii::$app->session->setFlash('error', 'Could not update payment status.');
        }

        return $this->redirect(['index', 'status' => $payment->status]);
    }
}

