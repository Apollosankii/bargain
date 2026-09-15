<?php

declare(strict_types=1);

namespace common\bootstrap;

use common\models\User;
use common\services\RbacService;
use Yii;
use yii\base\BootstrapInterface;
use yii\web\User as WebUser;

/**
 * Sync RBAC role assignment after login; ensure RBAC structure exists.
 */
class RbacBootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        if (!$app->has('authManager')) {
            return;
        }

        try {
            RbacService::initRolesAndPermissions();
        } catch (\Throwable $e) {
            Yii::warning('RBAC init skipped: ' . $e->getMessage(), __METHOD__);
        }

        if (!$app instanceof \yii\web\Application || !$app->has('user')) {
            return;
        }

        $app->user->on(WebUser::EVENT_AFTER_LOGIN, static function ($event): void {
            $identity = $event->identity;
            if ($identity instanceof User) {
                try {
                    RbacService::syncUser($identity);
                } catch (\Throwable $e) {
                    Yii::warning('RBAC sync failed for user #' . $identity->user_id . ': ' . $e->getMessage(), __METHOD__);
                }
            }
        });
    }
}
