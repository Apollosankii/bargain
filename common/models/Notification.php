<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $notification_id
 * @property int $user_id
 * @property int|null $auction_id
 * @property string $type
 * @property string $message
 * @property bool $read_status
 * @property string $created_at
 * @property int|null $template_id
 */
class Notification extends ActiveRecord
{
    public const TYPE_OUTBID = 'OUTBID';
    public const TYPE_BID_RECEIVED = 'BID_RECEIVED';
    public const TYPE_AUCTION_WON = 'AUCTION_WON';
    public const TYPE_AUCTION_CLOSED = 'AUCTION_CLOSED';
    public const TYPE_AUCTION_INTEREST = 'AUCTION_INTEREST';
    public const TYPE_SYSTEM_EVENT = 'SYSTEM_EVENT';

    public static function tableName(): string
    {
        return '{{%notifications}}';
    }

    public static function primaryKey(): array
    {
        return ['notification_id'];
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new Expression('CURRENT_TIMESTAMP'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['user_id', 'type', 'message'], 'required'],
            [['user_id', 'auction_id', 'template_id'], 'integer'],
            [['message'], 'string'],
            [['read_status'], 'boolean'],
            [['type'], 'in', 'range' => [
                self::TYPE_OUTBID,
                self::TYPE_BID_RECEIVED,
                self::TYPE_AUCTION_WON,
                self::TYPE_AUCTION_CLOSED,
                self::TYPE_AUCTION_INTEREST,
                self::TYPE_SYSTEM_EVENT,
            ]],
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['user_id' => 'user_id']);
    }

    public function getAuction(): ActiveQuery
    {
        return $this->hasOne(Auction::class, ['auction_id' => 'auction_id']);
    }

    public static function unreadCountFor(int $userId): int
    {
        return (int) static::find()
            ->where(['user_id' => $userId, 'read_status' => false])
            ->count();
    }

    /**
     * @return list<self>
     */
    public static function findForUser(int $userId, int $limit = 50): array
    {
        return static::find()
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    public function markRead(): bool
    {
        $this->read_status = true;

        return $this->save(false, ['read_status']);
    }

    public static function markAllRead(int $userId): int
    {
        return static::updateAll(
            ['read_status' => true],
            ['user_id' => $userId, 'read_status' => false],
        );
    }

    public static function notifySystem(int $userId, string $message, ?int $auctionId = null): bool
    {
        $notification = new self();
        $notification->user_id = $userId;
        $notification->auction_id = $auctionId;
        $notification->type = self::TYPE_SYSTEM_EVENT;
        $notification->message = $message;
        $notification->read_status = false;

        return $notification->save();
    }
}
