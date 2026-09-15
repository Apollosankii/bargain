<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Complaint;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ComplaintController extends Controller
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
                'actions' => [],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $request = Yii::$app->request;
        $status = trim((string) $request->get('status', ''));
        $type = trim((string) $request->get('type', ''));

        $query = Complaint::find()->with(['user', 'auction', 'reportedUser']);
        if ($status !== '') {
            $query->andWhere(['status' => $status]);
        }
        if ($type !== '') {
            $query->andWhere(['type' => $type]);
        }

        $pagination = new Pagination([
            'totalCount' => (int) $query->count(),
            'defaultPageSize' => 15,
        ]);

        $complaints = $query
            ->orderBy(['created_at' => SORT_DESC])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        return $this->render('index', [
            'complaints' => $complaints,
            'pagination' => $pagination,
            'filters' => ['status' => $status, 'type' => $type],
            'openCount' => (int) Complaint::find()->where(['status' => Complaint::STATUS_OPEN])->count(),
        ]);
    }

    public function actionView(int $id): string|Response
    {
        $complaint = $this->findComplaint($id);

        if (Yii::$app->request->isPost) {
            $status = (string) Yii::$app->request->post('status', $complaint->status);
            $adminNotes = trim((string) Yii::$app->request->post('admin_notes', ''));

            if (!isset(Complaint::statusLabels()[$status])) {
                Yii::$app->session->setFlash('error', 'Invalid status.');
                return $this->redirect(['view', 'id' => $id]);
            }

            $complaint->status = $status;
            $complaint->admin_notes = $adminNotes !== '' ? $adminNotes : null;
            $complaint->reviewed_by = (int) Yii::$app->user->id;
            $complaint->reviewed_at = date('Y-m-d H:i:s');

            if ($complaint->save(false, ['status', 'admin_notes', 'reviewed_by', 'reviewed_at', 'updated_at'])) {
                Yii::$app->session->setFlash('success', 'Complaint updated.');
            } else {
                Yii::$app->session->setFlash('error', 'Could not update complaint.');
            }

            return $this->redirect(['view', 'id' => $id]);
        }

        return $this->render('view', [
            'complaint' => $complaint,
        ]);
    }

    private function findComplaint(int $id): Complaint
    {
        $complaint = Complaint::find()
            ->where(['complaint_id' => $id])
            ->with(['user', 'auction', 'reportedUser', 'reviewer'])
            ->one();

        if ($complaint === null) {
            throw new NotFoundHttpException('Complaint not found.');
        }

        return $complaint;
    }
}
