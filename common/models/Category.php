<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $category_id
 * @property string $name
 * @property string|null $description
 */
class Category extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%categories}}';
    }

    public static function primaryKey(): array
    {
        return ['category_id'];
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 100],
            [['description'], 'string'],
            [['name'], 'unique'],
        ];
    }

    public function getAuctions(): ActiveQuery
    {
        return $this->hasMany(Auction::class, ['category_id' => 'category_id']);
    }

    /**
     * @return array<int, array{category_id:int,name:string,description:?string,active_auctions:int}>
     */
    public static function listWithActiveCounts(): array
    {
        return static::find()
            ->alias('c')
            ->select([
                'c.category_id',
                'c.name',
                'c.description',
                'active_auctions' => new Expression('COUNT(a.auction_id)'),
            ])
            ->leftJoin(
                ['a' => Auction::tableName()],
                "c.category_id = a.category_id AND a.status = 'ACTIVE' AND a.end_time > CURRENT_TIMESTAMP",
            )
            ->groupBy(['c.category_id', 'c.name', 'c.description'])
            ->orderBy(['c.name' => SORT_ASC])
            ->asArray()
            ->all();
    }

    public static function dropdownOptions(): array
    {
        return static::find()
            ->select(['name', 'category_id'])
            ->indexBy('category_id')
            ->orderBy(['name' => SORT_ASC])
            ->column();
    }
}
