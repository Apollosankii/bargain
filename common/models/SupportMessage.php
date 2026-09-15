<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $message_id
 * @property int $ticket_id
 * @property int $sender_id
 * @property bool $is_admin
 * @property string $body
 * @property string $created_at
 */
class SupportMessage extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%support_messages}}';
    }

    public static function primaryKey(): array
    {
        return ['message_id'];
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new Expression('CURRENT_TIMESTAMP'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['ticket_id', 'sender_id', 'body'], 'required'],
            [['ticket_id', 'sender_id'], 'integer'],
            [['body'], 'string'],
            [['is_admin'], 'boolean'],
            [['is_admin'], 'default', 'value' => false],
        ];
    }

    public function getTicket(): ActiveQuery
    {
        return $this->hasOne(SupportTicket::class, ['ticket_id' => 'ticket_id']);
    }

    public function getSender(): ActiveQuery
    {
        return $this->hasOne(User::class, ['user_id' => 'sender_id']);
    }
}
