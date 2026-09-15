<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $ticket_id
 * @property int $user_id
 * @property string $subject
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class SupportTicket extends ActiveRecord
{
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_REPLIED = 'REPLIED';
    public const STATUS_CLOSED = 'CLOSED';

    public static function tableName(): string
    {
        return '{{%support_tickets}}';
    }

    public static function primaryKey(): array
    {
        return ['ticket_id'];
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('CURRENT_TIMESTAMP'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['user_id', 'subject'], 'required'],
            [['user_id'], 'integer'],
            [['subject'], 'string', 'max' => 200],
            [['status'], 'in', 'range' => [self::STATUS_OPEN, self::STATUS_REPLIED, self::STATUS_CLOSED]],
            [['status'], 'default', 'value' => self::STATUS_OPEN],
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['user_id' => 'user_id']);
    }

    public function getMessages(): ActiveQuery
    {
        return $this->hasMany(SupportMessage::class, ['ticket_id' => 'ticket_id'])
            ->orderBy(['created_at' => SORT_ASC]);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_REPLIED => 'Replied',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'badge-warning',
            self::STATUS_REPLIED => 'badge-live',
            self::STATUS_CLOSED => 'badge-muted',
            default => 'badge-muted',
        };
    }
}
