<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Notification;
use common\models\SupportMessage;
use common\models\SupportTicket;
use common\models\User;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SupportController extends Controller
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
                    'reply' => ['post'],
                    'close' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $status = trim((string) Yii::$app->request->get('status', ''));

        $query = SupportTicket::find()->with(['user']);
        if ($status !== '') {
            $query->andWhere(['status' => $status]);
        }

        $pagination = new Pagination([
            'totalCount' => (int) $query->count(),
            'defaultPageSize' => 15,
        ]);

        $tickets = $query
            ->orderBy(['updated_at' => SORT_DESC])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        return $this->render('index', [
            'tickets' => $tickets,
            'pagination' => $pagination,
            'filters' => ['status' => $status],
            'openCount' => (int) SupportTicket::find()->where(['status' => SupportTicket::STATUS_OPEN])->count(),
        ]);
    }

    public function actionView(int $id): string|Response
    {
        $ticket = $this->findTicket($id);

        return $this->render('view', [
            'ticket' => $ticket,
            'messages' => $ticket->messages,
        ]);
    }

    public function actionReply(int $id): Response
    {
        $ticket = $this->findTicket($id);

        if ($ticket->status === SupportTicket::STATUS_CLOSED) {
            Yii::$app->session->setFlash('error', 'Ticket is closed.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $body = trim((string) Yii::$app->request->post('body', ''));
        if ($body === '') {
            Yii::$app->session->setFlash('error', 'Reply cannot be empty.');
            return $this->redirect(['view', 'id' => $id]);
        }

        /** @var User $admin */
        $admin = Yii::$app->user->identity;

        $message = new SupportMessage([
            'ticket_id' => $ticket->ticket_id,
            'sender_id' => $admin->user_id,
            'is_admin' => true,
            'body' => $body,
        ]);

        if ($message->save()) {
            $ticket->status = SupportTicket::STATUS_REPLIED;
            $ticket->save(false, ['status', 'updated_at']);

            Notification::notifySystem(
                (int) $ticket->user_id,
                "Support replied to your ticket: \"{$ticket->subject}\"",
            );

            Yii::$app->session->setFlash('success', 'Reply sent.');
        } else {
            Yii::$app->session->setFlash('error', 'Could not send reply.');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionClose(int $id): Response
    {
        $ticket = $this->findTicket($id);
        $ticket->status = SupportTicket::STATUS_CLOSED;
        $ticket->save(false, ['status', 'updated_at']);

        Notification::notifySystem(
            (int) $ticket->user_id,
            "Your support ticket \"{$ticket->subject}\" has been closed.",
        );

        Yii::$app->session->setFlash('success', 'Ticket closed.');
        return $this->redirect(['view', 'id' => $id]);
    }

    private function findTicket(int $id): SupportTicket
    {
        $ticket = SupportTicket::find()
            ->where(['ticket_id' => $id])
            ->with(['user', 'messages.sender'])
            ->one();

        if ($ticket === null) {
            throw new NotFoundHttpException('Ticket not found.');
        }

        return $ticket;
    }
}
