<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Complaint;
use common\models\SupportMessage;
use common\models\SupportTicket;
use common\models\User;
use Yii;
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
                        'roles' => ['auctioneer', 'bidder'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'reply' => ['post'],
                ],
            ],
        ];
    }

    public function actionComplaints(): string
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        $complaints = Complaint::find()
            ->where(['user_id' => $user->user_id])
            ->with(['auction', 'reportedUser'])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('complaints', [
            'complaints' => $complaints,
        ]);
    }

    public function actionCreateComplaint(): string|Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $model = new Complaint(['user_id' => $user->user_id]);

        $auctionId = (int) Yii::$app->request->get('auction_id', 0);
        if ($auctionId > 0) {
            $model->type = Complaint::TYPE_AUCTION;
            $model->auction_id = $auctionId;
        }

        $reportedUserId = (int) Yii::$app->request->get('reported_user_id', 0);
        if ($reportedUserId > 0) {
            $model->type = Complaint::TYPE_USER;
            $model->reported_user_id = $reportedUserId;
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Your complaint has been submitted. Our team will review it shortly.');
            return $this->redirect(['complaints']);
        }

        return $this->render('create-complaint', [
            'model' => $model,
        ]);
    }

    public function actionSupport(): string
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        $tickets = SupportTicket::find()
            ->where(['user_id' => $user->user_id])
            ->orderBy(['updated_at' => SORT_DESC])
            ->all();

        return $this->render('support', [
            'tickets' => $tickets,
        ]);
    }

    public function actionCreateTicket(): string|Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $ticket = new SupportTicket(['user_id' => $user->user_id]);
        $message = new SupportMessage([
            'sender_id' => $user->user_id,
            'is_admin' => false,
        ]);

        $body = '';
        if ($ticket->load(Yii::$app->request->post()) && ($body = trim((string) Yii::$app->request->post('body', ''))) !== '') {
            $db = Yii::$app->db;
            $tx = $db->beginTransaction();
            try {
                if (!$ticket->save()) {
                    throw new \RuntimeException('Could not create ticket.');
                }
                $message->ticket_id = (int) $ticket->ticket_id;
                $message->body = $body;
                if (!$message->save()) {
                    throw new \RuntimeException('Could not save message.');
                }
                $tx->commit();
                Yii::$app->session->setFlash('success', 'Support ticket created. We will respond as soon as possible.');
                return $this->redirect(['view-ticket', 'id' => $ticket->ticket_id]);
            } catch (\Throwable $e) {
                $tx->rollBack();
                Yii::$app->session->setFlash('error', 'Could not submit your ticket. Please try again.');
            }
        }

        return $this->render('create-ticket', [
            'ticket' => $ticket,
            'body' => $body,
        ]);
    }

    public function actionViewTicket(int $id): string|Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $ticket = $this->findUserTicket($id, (int) $user->user_id);

        return $this->render('view-ticket', [
            'ticket' => $ticket,
            'messages' => $ticket->messages,
        ]);
    }

    public function actionReply(int $id): Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $ticket = $this->findUserTicket($id, (int) $user->user_id);

        if ($ticket->status === SupportTicket::STATUS_CLOSED) {
            Yii::$app->session->setFlash('error', 'This ticket is closed.');
            return $this->redirect(['view-ticket', 'id' => $id]);
        }

        $body = trim((string) Yii::$app->request->post('body', ''));
        if ($body === '') {
            Yii::$app->session->setFlash('error', 'Message cannot be empty.');
            return $this->redirect(['view-ticket', 'id' => $id]);
        }

        $message = new SupportMessage([
            'ticket_id' => $ticket->ticket_id,
            'sender_id' => $user->user_id,
            'is_admin' => false,
            'body' => $body,
        ]);

        if ($message->save()) {
            $ticket->status = SupportTicket::STATUS_OPEN;
            $ticket->save(false, ['status', 'updated_at']);
            Yii::$app->session->setFlash('success', 'Reply sent.');
        } else {
            Yii::$app->session->setFlash('error', 'Could not send reply.');
        }

        return $this->redirect(['view-ticket', 'id' => $id]);
    }

    private function findUserTicket(int $id, int $userId): SupportTicket
    {
        $ticket = SupportTicket::find()
            ->where(['ticket_id' => $id, 'user_id' => $userId])
            ->one();

        if ($ticket === null) {
            throw new NotFoundHttpException('Ticket not found.');
        }

        return $ticket;
    }
}
