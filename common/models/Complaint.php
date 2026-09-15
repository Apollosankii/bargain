<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $complaint_id
 * @property int $user_id
 * @property string $type
 * @property int|null $auction_id
 * @property int|null $reported_user_id
 * @property string $subject
 * @property string $description
 * @property string $status
 * @property string|null $admin_notes
 * @property int|null $reviewed_by
 * @property string|null $reviewed_at
 * @property string $created_at
 * @property string $updated_at
 */
class Complaint extends ActiveRecord
{
    public const TYPE_AUCTION = 'AUCTION';
    public const TYPE_USER = 'USER';
    public const TYPE_OTHER = 'OTHER';

    public const STATUS_OPEN = 'OPEN';
    public const STATUS_UNDER_REVIEW = 'UNDER_REVIEW';
    public const STATUS_RESOLVED = 'RESOLVED';
    public const STATUS_DISMISSED = 'DISMISSED';

    public static function tableName(): string
    {
        return '{{%complaints}}';
    }

    public static function primaryKey(): array
    {
        return ['complaint_id'];
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
            [['user_id', 'type', 'subject', 'description'], 'required'],
            [['user_id', 'auction_id', 'reported_user_id', 'reviewed_by'], 'integer'],
            [['description', 'admin_notes'], 'string'],
            [['subject'], 'string', 'max' => 200],
            [['type'], 'in', 'range' => [self::TYPE_AUCTION, self::TYPE_USER, self::TYPE_OTHER]],
            [['status'], 'in', 'range' => [
                self::STATUS_OPEN,
                self::STATUS_UNDER_REVIEW,
                self::STATUS_RESOLVED,
                self::STATUS_DISMISSED,
            ]],
            [['status'], 'default', 'value' => self::STATUS_OPEN],
            [['auction_id'], 'required', 'when' => static fn (self $m) => $m->type === self::TYPE_AUCTION],
            [['reported_user_id'], 'required', 'when' => static fn (self $m) => $m->type === self::TYPE_USER],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'type' => 'Complaint type',
            'auction_id' => 'Auction',
            'reported_user_id' => 'Reported user',
            'subject' => 'Subject',
            'description' => 'Details',
            'admin_notes' => 'Admin notes',
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

    public function getReportedUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['user_id' => 'reported_user_id']);
    }

    public function getReviewer(): ActiveQuery
    {
        return $this->hasOne(User::class, ['user_id' => 'reviewed_by']);
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_AUCTION => 'Report an auction',
            self::TYPE_USER => 'Report a user',
            self::TYPE_OTHER => 'Other issue',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_UNDER_REVIEW => 'Under review',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_DISMISSED => 'Dismissed',
        ];
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'badge-warning',
            self::STATUS_UNDER_REVIEW => 'badge-live',
            self::STATUS_RESOLVED => 'badge-success',
            self::STATUS_DISMISSED => 'badge-muted',
            default => 'badge-muted',
        };
    }

    /** Summary text admins can reuse when taking action. */
    public function actionReasonSummary(): string
    {
        $parts = [trim($this->subject)];
        $detail = trim($this->description);
        if ($detail !== '') {
            $parts[] = mb_strlen($detail) > 200 ? mb_substr($detail, 0, 200) . '…' : $detail;
        }
        return implode(' — ', $parts);
    }
}
