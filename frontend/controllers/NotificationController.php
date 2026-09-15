<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Notification;
use common\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class NotificationController extends Controller
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
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'read' => ['post'],
                    'read-all' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $notifications = Notification::findForUser((int) $user->user_id);

        return $this->render('index', [
            'notifications' => $notifications,
        ]);
    }

    public function actionRead(int $id): Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $notification = Notification::findOne([
            'notification_id' => $id,
            'user_id' => $user->user_id,
        ]);

        if ($notification === null) {
            throw new NotFoundHttpException('Notification not found.');
        }

        $notification->markRead();

        return $this->redirect(['index']);
    }

    public function actionReadAll(): Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        Notification::markAllRead((int) $user->user_id);
        Yii::$app->session->setFlash('success', 'All notifications marked as read.');

        return $this->redirect(['index']);
    }
}
