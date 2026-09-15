<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Auction;
use common\models\Bid;
use common\models\Notification;
use common\models\Payment;
use common\models\User;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AdminController extends Controller
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
                    'user-status' => ['post'],
                    'auction-status' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $request = Yii::$app->request;

        $userQ = trim((string) $request->get('user_q', ''));
        $userRole = trim((string) $request->get('user_role', ''));
        $userStatus = trim((string) $request->get('user_status', ''));

        $usersQuery = User::find();
        if ($userQ !== '') {
            $usersQuery->andWhere([
                'or',
                ['ilike', 'first_name', $userQ],
                ['ilike', 'last_name', $userQ],
                ['ilike', 'email', $userQ],
                ['ilike', 'phone', $userQ],
            ]);
        }
        if ($userRole !== '') {
            $usersQuery->andWhere(['role' => $userRole]);
        }
        if ($userStatus !== '') {
            $usersQuery->andWhere(['status' => $userStatus]);
        }

        $usersPagination = new Pagination([
            'totalCount' => (int) $usersQuery->count(),
            'defaultPageSize' => 10,
            'pageParam' => 'user_page',
        ]);

        $users = $usersQuery
            ->orderBy(['created_at' => SORT_DESC])
            ->offset($usersPagination->offset)
            ->limit($usersPagination->limit)
            ->all();

        $auctionQ = trim((string) $request->get('auction_q', ''));
        $auctionStatus = trim((string) $request->get('auction_status', ''));

        $auctionsQuery = Auction::find()->alias('a');
        if ($auctionQ !== '') {
            $auctionsQuery->andWhere([
                'or',
                ['ilike', 'a.title', $auctionQ],
                ['ilike', 'a.description', $auctionQ],
            ]);
        }
        if ($auctionStatus !== '') {
            $auctionsQuery->andWhere(['a.status' => $auctionStatus]);
        }

        $auctionsPagination = new Pagination([
            'totalCount' => (int) $auctionsQuery->count(),
            'defaultPageSize' => 10,
            'pageParam' => 'auction_page',
        ]);

        $auctions = $auctionsQuery
            ->orderBy(['a.created_at' => SORT_DESC])
            ->offset($auctionsPagination->offset)
            ->limit($auctionsPagination->limit)
            ->all();

        $notifications = Notification::find()
            ->with(['user'])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(20)
            ->all();

        $analytics = [
            'users' => (int) User::find()->where(['!=', 'role', User::ROLE_ADMIN])->count(),
            'activeAuctions' => (int) Auction::find()->where(['status' => Auction::STATUS_ACTIVE])->count(),
            'bids' => (int) Bid::find()->count(),
            'paymentsPending' => (int) Payment::find()->where(['status' => Payment::STATUS_PENDING])->count(),
            'paymentsCompleted' => (int) Payment::find()->where(['status' => Payment::STATUS_COMPLETED])->count(),
        ];

        return $this->render('index', [
            'users' => $users,
            'usersPagination' => $usersPagination,
            'auctions' => $auctions,
            'auctionsPagination' => $auctionsPagination,
            'notifications' => $notifications,
            'analytics' => $analytics,
            'filters' => [
                'user_q' => $userQ,
                'user_role' => $userRole,
                'user_status' => $userStatus,
                'auction_q' => $auctionQ,
                'auction_status' => $auctionStatus,
            ],
        ]);
    }

    public function actionUserStatus(int $id): Response
    {
        $user = User::findOne($id);
        if ($user === null) {
            throw new NotFoundHttpException('User not found.');
        }

        if ($user->isAdmin()) {
            Yii::$app->session->setFlash('error', 'Cannot change admin account status.');
            return $this->redirect(Yii::$app->request->referrer ?: ['index']);
        }

        $status = (string) Yii::$app->request->post('status', '');
        if (!in_array($status, [User::STATUS_ACTIVE, User::STATUS_INACTIVE], true)) {
            Yii::$app->session->setFlash('error', 'Invalid user status.');
            return $this->redirect(Yii::$app->request->referrer ?: ['index']);
        }

        $reason = trim((string) Yii::$app->request->post('reason', ''));
        $isDeactivate = $status === User::STATUS_INACTIVE;

        if ($isDeactivate && $reason === '') {
            Yii::$app->session->setFlash('error', 'A reason is required to deactivate a user.');
            return $this->redirect(Yii::$app->request->referrer ?: ['index']);
        }

        $user->status = $status;
        $ok = $user->save(false, ['status']);

        if ($ok) {
            $this->logAdminAction(
                'USER',
                (int) $user->user_id,
                $isDeactivate ? 'DEACTIVATE' : 'REACTIVATE',
                $reason,
            );

            if ($isDeactivate) {
                Notification::notifySystem(
                    (int) $user->user_id,
                    "Your account has been deactivated by an administrator. Reason: {$reason}",
                );
            }

            Yii::$app->session->setFlash(
                'success',
                $isDeactivate ? 'User deactivated.' : 'User reactivated.',
            );
        } else {
            Yii::$app->session->setFlash('error', 'Could not update user status.');
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

    public function actionAuctionStatus(int $id): Response
    {
        $auction = Auction::findOne($id);
        if ($auction === null) {
            throw new NotFoundHttpException('Auction not found.');
        }

        $status = (string) Yii::$app->request->post('status', '');
        if (!in_array($status, [Auction::STATUS_ACTIVE, Auction::STATUS_CANCELLED], true)) {
            Yii::$app->session->setFlash('error', 'Invalid auction status.');
            return $this->redirect(Yii::$app->request->referrer ?: ['index']);
        }

        $reason = trim((string) Yii::$app->request->post('reason', ''));
        $isTakeDown = $status === Auction::STATUS_CANCELLED;
        $previousStatus = $auction->status;

        if ($isTakeDown) {
            if ($previousStatus === Auction::STATUS_CANCELLED) {
                Yii::$app->session->setFlash('error', 'Auction is already taken down.');
                return $this->redirect(Yii::$app->request->referrer ?: ['index']);
            }

            if ($reason === '') {
                Yii::$app->session->setFlash('error', 'A reason is required to take down an auction.');
                return $this->redirect(Yii::$app->request->referrer ?: ['index']);
            }
        } elseif ($previousStatus !== Auction::STATUS_CANCELLED) {
            Yii::$app->session->setFlash('error', 'Only cancelled auctions can be reactivated.');
            return $this->redirect(Yii::$app->request->referrer ?: ['index']);
        }

        $auction->status = $status;
        $ok = $auction->save(false, ['status']);

        if ($ok) {
            $action = $isTakeDown
                ? ($previousStatus === Auction::STATUS_CLOSED ? 'REMOVE' : 'DEACTIVATE')
                : 'REACTIVATE';

            $this->logAdminAction(
                'AUCTION',
                (int) $auction->auction_id,
                $action,
                $reason,
            );

            if ($isTakeDown) {
                Notification::notifySystem(
                    (int) $auction->auctioneer_id,
                    "Your auction \"{$auction->title}\" was taken down by an administrator. Reason: {$reason}",
                    (int) $auction->auction_id,
                );
            }

            Yii::$app->session->setFlash(
                'success',
                $isTakeDown ? 'Auction taken down.' : 'Auction reactivated.',
            );
        } else {
            Yii::$app->session->setFlash('error', 'Could not update auction status.');
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

    private function logAdminAction(string $targetType, int $targetId, string $action, string $reason): void
    {
        $adminId = (int) Yii::$app->user->id;
        Yii::$app->db->createCommand()->insert('admin_actions', [
            'admin_id' => $adminId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'action' => $action,
            'reason' => $reason !== '' ? $reason : null,
            'performed_at' => date('Y-m-d H:i:s'),
        ])->execute();
    }
}

