<?php

declare(strict_types=1);

namespace console\controllers;

use common\services\RbacService;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class RbacController extends Controller
{
    /**
     * Create roles, permissions, and role hierarchy.
     */
    public function actionInit(): int
    {
        RbacService::initRolesAndPermissions();
        $this->stdout("RBAC roles and permissions initialized.\n");

        return ExitCode::OK;
    }

    /**
     * Assign RBAC roles to all users based on users.role column.
     */
    public function actionSyncAll(): int
    {
        RbacService::initRolesAndPermissions();
        $count = RbacService::syncAllUsers();
        $this->stdout("Synced RBAC assignments for {$count} user(s).\n");

        return ExitCode::OK;
    }
}
