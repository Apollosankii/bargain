<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $image_id
 * @property int $auction_id
 * @property string $image_url
 * @property int $sort_order
 * @property string $created_at
 *
 * @property-read Auction $auction
 */
class AuctionImage extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%auction_images}}';
    }

    public static function primaryKey(): array
    {
        return ['image_id'];
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
            [['auction_id', 'image_url'], 'required'],
            [['auction_id', 'sort_order'], 'integer'],
            [['image_url'], 'string', 'max' => 500],
        ];
    }

    public function getAuction(): ActiveQuery
    {
        return $this->hasOne(Auction::class, ['auction_id' => 'auction_id']);
    }
}
