<?php

declare(strict_types=1);

namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * Bargain user model (matches PostgreSQL `users` table).
 *
 * @property int $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property string $password_hash
 * @property string $role AUCTIONEER|BIDDER|ADMIN
 * @property string $status ACTIVE|INACTIVE
 * @property string|null $profile_photo
 * @property string $created_at
 * @property string|null $auth_key
 * @property string $password write-only password
 */
class User extends ActiveRecord implements IdentityInterface
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    public const ROLE_AUCTIONEER = 'AUCTIONEER';
    public const ROLE_BIDDER = 'BIDDER';
    public const ROLE_ADMIN = 'ADMIN';

    public static function tableName(): string
    {
        return '{{%users}}';
    }

    public static function primaryKey(): array
    {
        return ['user_id'];
    }

    public function rules(): array
    {
        return [
            [['first_name', 'last_name', 'email', 'phone', 'password_hash', 'role'], 'required'],
            [['first_name', 'last_name'], 'string', 'max' => 50],
            [['email'], 'string', 'max' => 100],
            [['phone'], 'string', 'max' => 20],
            [['profile_photo'], 'string', 'max' => 500],
            [['email'], 'email'],
            [['email'], 'unique'],
            [['phone'], 'unique'],
            [['role'], 'in', 'range' => [self::ROLE_AUCTIONEER, self::ROLE_BIDDER, self::ROLE_ADMIN]],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['auth_key'], 'string', 'max' => 32],
        ];
    }

    public static function findIdentity($id): ?self
    {
        return static::findOne(['user_id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findIdentityByAccessToken($token, $type = null): never
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    public static function findByEmail(string $email): ?self
    {
        return static::findOne([
            'email' => mb_strtolower(trim($email)),
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /** @deprecated use findByEmail */
    public static function findByUsername(string $username): ?self
    {
        return static::findByEmail($username);
    }

    public function getId(): int|string
    {
        return $this->user_id;
    }

    public function getAuthKey(): ?string
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->getAuthKey() === $authKey;
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function isBidder(): bool
    {
        return $this->role === self::ROLE_BIDDER;
    }

    public function isAuctioneer(): bool
    {
        return $this->role === self::ROLE_AUCTIONEER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function hasActiveSubscription(): bool
    {
        return Subscription::isActiveFor((int) $this->user_id);
    }

    public function requiresSubscriptionGate(): bool
    {
        return $this->isAuctioneer() && !$this->hasActiveSubscription();
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert && $this->email) {
            $this->email = mb_strtolower(trim($this->email));
        }

        if ($insert && empty($this->auth_key)) {
            $this->generateAuthKey();
        }

        return true;
    }
}
