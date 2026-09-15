<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\User;
use Yii;
use yii\data\Pagination;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\web\Controller;

class AuditController extends Controller
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
        ];
    }

    public function actionIndex(): string
    {
        $request = Yii::$app->request;
        $targetType = trim((string) $request->get('target_type', ''));
        $action = trim((string) $request->get('action', ''));

        $query = (new Query())
            ->from(['aa' => 'admin_actions'])
            ->leftJoin(['u' => '{{%users}}'], 'u.user_id = aa.admin_id');

        if ($targetType !== '') {
            $query->andWhere(['aa.target_type' => $targetType]);
        }
        if ($action !== '') {
            $query->andWhere(['aa.action' => $action]);
        }

        $pagination = new Pagination([
            'totalCount' => (int) $query->count('*', Yii::$app->db),
            'defaultPageSize' => 25,
        ]);

        $rows = $query
            ->select([
                'aa.action_id',
                'aa.admin_id',
                'aa.target_type',
                'aa.target_id',
                'aa.action',
                'aa.reason',
                'aa.performed_at',
                'admin_name' => new \yii\db\Expression("u.first_name || ' ' || u.last_name"),
                'admin_email' => 'u.email',
            ])
            ->orderBy(['aa.performed_at' => SORT_DESC])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all(Yii::$app->db);

        $targetTypes = (new Query())
            ->select(['target_type'])
            ->distinct()
            ->from('admin_actions')
            ->orderBy(['target_type' => SORT_ASC])
            ->column(Yii::$app->db);

        $actions = (new Query())
            ->select(['action'])
            ->distinct()
            ->from('admin_actions')
            ->orderBy(['action' => SORT_ASC])
            ->column(Yii::$app->db);

        return $this->render('index', [
            'rows' => $rows,
            'pagination' => $pagination,
            'filters' => ['target_type' => $targetType, 'action' => $action],
            'targetTypes' => $targetTypes,
            'actions' => $actions,
        ]);
    }
}
