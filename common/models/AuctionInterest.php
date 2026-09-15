<?php

declare(strict_types=1);

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $interest_id
 * @property int $user_id
 * @property int $auction_id
 * @property string|null $notified_at
 * @property string $created_at
 *
 * @property-read User $user
 * @property-read Auction $auction
 */
class AuctionInterest extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%auction_interest}}';
    }

    public static function primaryKey(): array
    {
        return ['interest_id'];
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
            [['user_id', 'auction_id'], 'required'],
            [['user_id', 'auction_id'], 'integer'],
            [['user_id', 'auction_id'], 'unique', 'targetAttribute' => ['user_id', 'auction_id']],
            [['notified_at', 'created_at'], 'safe'],
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

    public static function isInterested(int $userId, int $auctionId): bool
    {
        return static::find()
            ->where(['user_id' => $userId, 'auction_id' => $auctionId])
            ->exists();
    }

    /**
     * @return array{ok: bool, interested: bool, message: string}
     */
    public static function toggle(int $userId, Auction $auction): array
    {
        if (!$auction->isScheduled()) {
            return [
                'ok' => false,
                'interested' => false,
                'message' => 'You can only mark interest on auctions that have not started yet.',
            ];
        }

        $existing = static::findOne([
            'user_id' => $userId,
            'auction_id' => $auction->auction_id,
        ]);

        if ($existing !== null) {
            $existing->delete();

            return [
                'ok' => true,
                'interested' => false,
                'message' => 'Interest removed. You will not be notified when bidding opens.',
            ];
        }

        $interest = new self();
        $interest->user_id = $userId;
        $interest->auction_id = (int) $auction->auction_id;
        $interest->notified_at = null;

        if (!$interest->save()) {
            return [
                'ok' => false,
                'interested' => false,
                'message' => 'Could not save your interest. Please try again.',
            ];
        }

        return [
            'ok' => true,
            'interested' => true,
            'message' => 'Marked! We will notify you when this auction opens for bidding.',
        ];
    }

    /**
     * Notify interested bidders for auctions that have opened since they marked interest.
     */
    public static function notifyOpenedAuctions(): int
    {
        $pending = static::find()
            ->alias('i')
            ->innerJoinWith(['auction a'])
            ->where(['i.notified_at' => null])
            ->andWhere(['a.status' => Auction::STATUS_ACTIVE])
            ->andWhere(['<=', 'a.start_time', new Expression('CURRENT_TIMESTAMP')])
            ->andWhere(['>', 'a.end_time', new Expression('CURRENT_TIMESTAMP')])
            ->all();

        $count = 0;

        foreach ($pending as $interest) {
            /** @var self $interest */
            $auction = $interest->auction;
            if ($auction === null) {
                continue;
            }

            $notification = new Notification();
            $notification->user_id = (int) $interest->user_id;
            $notification->auction_id = (int) $interest->auction_id;
            $notification->type = Notification::TYPE_AUCTION_INTEREST;
            $notification->message = 'Bidding is now open for "' . $auction->title . '". Place your bid!';
            $notification->read_status = false;

            if (!$notification->save()) {
                Yii::warning(
                    'Failed to notify interest #' . $interest->interest_id,
                    __METHOD__,
                );
                continue;
            }

            $interest->notified_at = date('Y-m-d H:i:s');
            $interest->save(false, ['notified_at']);
            $count++;
        }

        return $count;
    }

    /**
     * Run open notifications at most once per minute (shared across requests).
     */
    public static function notifyOpenedAuctionsThrottled(): int
    {
        $cache = Yii::$app->cache;
        $key = 'auction_interest_notify_opened';
        if ($cache->get($key)) {
            return 0;
        }

        $cache->set($key, 1, 60);

        return self::notifyOpenedAuctions();
    }
}
