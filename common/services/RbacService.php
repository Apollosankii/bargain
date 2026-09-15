<?php

declare(strict_types=1);

namespace common\services;

use common\models\User;
use Yii;
use yii\rbac\ManagerInterface;
use yii\web\ForbiddenHttpException;

/**
 * Yii2 RBAC roles and permissions for Bargain.
 *
 * Roles map from users.role: ADMIN → admin, AUCTIONEER → auctioneer, BIDDER → bidder.
 */
class RbacService
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_AUCTIONEER = 'auctioneer';
    public const ROLE_BIDDER = 'bidder';

    public const PERM_ACCESS_ADMIN = 'accessAdminPortal';
    public const PERM_MANAGE_USERS = 'manageUsers';
    public const PERM_MANAGE_AUCTIONS = 'manageAuctions';
    public const PERM_MANAGE_PAYMENTS = 'managePayments';
    public const PERM_VIEW_AUDIT = 'viewAuditLog';
    public const PERM_MANAGE_COMPLAINTS = 'manageComplaints';
    public const PERM_MANAGE_SUPPORT = 'manageSupport';
    public const PERM_CREATE_AUCTION = 'createAuction';
    public const PERM_PLACE_BID = 'placeBid';
    public const PERM_MANAGE_SUBSCRIPTION = 'manageSubscription';
    public const PERM_USE_SUPPORT = 'useSupport';

    public static function auth(): ManagerInterface
    {
        return Yii::$app->authManager;
    }

    /**
     * Create roles, permissions, and hierarchy (idempotent).
     */
    public static function initRolesAndPermissions(): void
    {
        $auth = self::auth();

        $permissions = [
            self::PERM_ACCESS_ADMIN => 'Access the admin backend portal',
            self::PERM_MANAGE_USERS => 'Manage user accounts',
            self::PERM_MANAGE_AUCTIONS => 'Manage auctions (take down, reactivate)',
            self::PERM_MANAGE_PAYMENTS => 'Manage payment records',
            self::PERM_VIEW_AUDIT => 'View admin audit log',
            self::PERM_MANAGE_COMPLAINTS => 'Review user complaints',
            self::PERM_MANAGE_SUPPORT => 'Reply to support tickets',
            self::PERM_CREATE_AUCTION => 'Create and manage own auctions',
            self::PERM_PLACE_BID => 'Place bids on auctions',
            self::PERM_MANAGE_SUBSCRIPTION => 'Manage auctioneer subscription',
            self::PERM_USE_SUPPORT => 'Contact support and file complaints',
        ];

        foreach ($permissions as $name => $description) {
            if ($auth->getPermission($name) === null) {
                $perm = $auth->createPermission($name);
                $perm->description = $description;
                $auth->add($perm);
            }
        }

        $roles = [
            self::ROLE_ADMIN => 'Platform administrator',
            self::ROLE_AUCTIONEER => 'Auctioneer (seller)',
            self::ROLE_BIDDER => 'Bidder (buyer)',
        ];

        foreach ($roles as $name => $description) {
            if ($auth->getRole($name) === null) {
                $role = $auth->createRole($name);
                $role->description = $description;
                $auth->add($role);
            }
        }

        self::linkChild(self::ROLE_ADMIN, self::PERM_ACCESS_ADMIN);
        self::linkChild(self::ROLE_ADMIN, self::PERM_MANAGE_USERS);
        self::linkChild(self::ROLE_ADMIN, self::PERM_MANAGE_AUCTIONS);
        self::linkChild(self::ROLE_ADMIN, self::PERM_MANAGE_PAYMENTS);
        self::linkChild(self::ROLE_ADMIN, self::PERM_VIEW_AUDIT);
        self::linkChild(self::ROLE_ADMIN, self::PERM_MANAGE_COMPLAINTS);
        self::linkChild(self::ROLE_ADMIN, self::PERM_MANAGE_SUPPORT);

        self::linkChild(self::ROLE_AUCTIONEER, self::PERM_CREATE_AUCTION);
        self::linkChild(self::ROLE_AUCTIONEER, self::PERM_MANAGE_SUBSCRIPTION);
        self::linkChild(self::ROLE_AUCTIONEER, self::PERM_USE_SUPPORT);

        self::linkChild(self::ROLE_BIDDER, self::PERM_PLACE_BID);
        self::linkChild(self::ROLE_BIDDER, self::PERM_USE_SUPPORT);
    }

    public static function rbacRoleFor(User $user): string
    {
        return match ($user->role) {
            User::ROLE_ADMIN => self::ROLE_ADMIN,
            User::ROLE_AUCTIONEER => self::ROLE_AUCTIONEER,
            User::ROLE_BIDDER => self::ROLE_BIDDER,
            default => self::ROLE_BIDDER,
        };
    }

    /**
     * Assign the RBAC role matching the user's account role; revoke other assignments.
     */
    public static function syncUser(User $user): void
    {
        $auth = self::auth();
        $roleName = self::rbacRoleFor($user);
        $userId = (string) $user->user_id;

        $auth->revokeAll($userId);

        $role = $auth->getRole($roleName);
        if ($role !== null) {
            $auth->assign($role, $userId);
        }
    }

    /**
     * Sync RBAC assignments for every active user.
     *
     * @return int Number of users synced
     */
    public static function syncAllUsers(): int
    {
        $users = User::find()->all();
        foreach ($users as $user) {
            self::syncUser($user);
        }

        return count($users);
    }

    public static function requireRole(string $roleName): void
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->can($roleName)) {
            throw new ForbiddenHttpException('You do not have access to this page.');
        }
    }

    /**
     * Map legacy User::ROLE_* constant to RBAC role name.
     */
    public static function roleFromUserConstant(string $userRole): string
    {
        return match ($userRole) {
            User::ROLE_ADMIN => self::ROLE_ADMIN,
            User::ROLE_AUCTIONEER => self::ROLE_AUCTIONEER,
            User::ROLE_BIDDER => self::ROLE_BIDDER,
            default => $userRole,
        };
    }

    private static function linkChild(string $parent, string $child): void
    {
        $auth = self::auth();
        $parentItem = $auth->getRole($parent) ?? $auth->getPermission($parent);
        $childItem = $auth->getPermission($child) ?? $auth->getRole($child);

        if ($parentItem !== null && $childItem !== null && !$auth->hasChild($parentItem, $childItem)) {
            $auth->addChild($parentItem, $childItem);
        }
    }
}
