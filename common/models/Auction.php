<?php

declare(strict_types=1);

namespace common\models;

use common\services\MailService;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $auction_id
 * @property int $auctioneer_id
 * @property int $category_id
 * @property string $title
 * @property string|null $description
 * @property string|null $image_url
 * @property string $starting_bid
 * @property string $current_bid
 * @property string $start_time
 * @property string $end_time
 * @property string $status ACTIVE|CLOSED|CANCELLED
 * @property string $created_at
 *
 * @property-read User $auctioneer
 * @property-read Category $category
 * @property-read Bid[] $bids
 * @property-read Bid|null $winningBid
 * @property-read AuctionImage[] $images
 * @property-read int $totalBids
 */
class Auction extends ActiveRecord
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_CLOSED = 'CLOSED';
    public const STATUS_CANCELLED = 'CANCELLED';

    /** @var int|string populated via select for listings */
    public $total_bids;
    public $category_name;
    public $final_price;
    public $winner_first_name;
    public $winner_last_name;

    public static function tableName(): string
    {
        return '{{%auctions}}';
    }

    public static function primaryKey(): array
    {
        return ['auction_id'];
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
            [['auctioneer_id', 'category_id', 'title', 'starting_bid', 'current_bid', 'start_time', 'end_time'], 'required'],
            [['auctioneer_id', 'category_id'], 'integer'],
            [['title'], 'string', 'max' => 200],
            [['description'], 'string'],
            [['image_url'], 'string', 'max' => 500],
            [['starting_bid', 'current_bid'], 'number', 'min' => 0.01],
            [['start_time', 'end_time'], 'safe'],
            [
                ['end_time'],
                'compare',
                'compareAttribute' => 'start_time',
                'operator' => '>',
                'message' => 'End time must be after start time.',
            ],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_CLOSED, self::STATUS_CANCELLED]],
            [['category_id'], 'exist', 'targetClass' => Category::class, 'targetAttribute' => ['category_id' => 'category_id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'title' => 'Title',
            'description' => 'Description',
            'category_id' => 'Category',
            'image_url' => 'Image',
            'starting_bid' => 'Starting Bid (KES)',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
        ];
    }

    public function getAuctioneer(): ActiveQuery
    {
        return $this->hasOne(User::class, ['user_id' => 'auctioneer_id']);
    }

    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(Category::class, ['category_id' => 'category_id']);
    }

    public function getBids(): ActiveQuery
    {
        return $this->hasMany(Bid::class, ['auction_id' => 'auction_id']);
    }

    public function getWinningBid(): ActiveQuery
    {
        return $this->hasOne(Bid::class, ['auction_id' => 'auction_id'])
            ->andWhere(['is_winning' => true]);
    }

    public function getImages(): ActiveQuery
    {
        return $this->hasMany(AuctionImage::class, ['auction_id' => 'auction_id'])
            ->orderBy(['sort_order' => SORT_ASC, 'image_id' => SORT_ASC]);
    }

    /**
     * @return list<string>
     */
    public function getGalleryUrls(): array
    {
        $urls = [];
        foreach ($this->images as $image) {
            if ($image->image_url !== '') {
                $urls[] = $image->image_url;
            }
        }

        if ($urls === [] && $this->image_url) {
            $urls[] = $this->image_url;
        }

        return $urls;
    }

    public function isTimedOut(): bool
    {
        return strtotime((string) $this->end_time) <= time();
    }

    /**
     * Timed-out / closed auction with no bids — auctioneer may extend.
     */
    public function canExtend(): bool
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return false;
        }

        if ($this->status === self::STATUS_ACTIVE && !$this->isTimedOut()) {
            return false;
        }

        if ($this->status !== self::STATUS_ACTIVE && $this->status !== self::STATUS_CLOSED) {
            return false;
        }

        return $this->getBids()->count() === 0;
    }

    /**
     * Extend a timed-out no-bid auction so bidding can resume.
     */
    public function extendUntil(string $newEndTime): bool
    {
        if (!$this->canExtend()) {
            return false;
        }

        $endTs = strtotime($newEndTime);
        if ($endTs === false || $endTs <= time()) {
            return false;
        }

        $this->end_time = date('Y-m-d H:i:s', $endTs);
        $this->status = self::STATUS_ACTIVE;

        // Ensure the auction is open for bidding immediately after extend.
        if (strtotime((string) $this->start_time) > time()) {
            $this->start_time = date('Y-m-d H:i:s');
        }

        if (!$this->save(false, ['end_time', 'status', 'start_time'])) {
            return false;
        }

        // Notify bidders who marked interest before it timed out / while scheduled.
        $interests = AuctionInterest::find()
            ->where(['auction_id' => $this->auction_id])
            ->all();

        foreach ($interests as $interest) {
            Notification::notifySystem(
                (int) $interest->user_id,
                'Auction "' . $this->title . '" has been extended and is open for bidding again.',
                (int) $this->auction_id,
            );
            $interest->notified_at = date('Y-m-d H:i:s');
            $interest->save(false, ['notified_at']);
        }

        return true;
    }

    public function getTotalBids(): int
    {
        if ($this->total_bids !== null) {
            return (int) $this->total_bids;
        }

        return (int) $this->getBids()->count();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && strtotime((string) $this->end_time) > time();
    }

    public function hasStarted(): bool
    {
        return strtotime((string) $this->start_time) <= time();
    }

    /**
     * ACTIVE auction that is waiting for its start time.
     */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && !$this->hasStarted()
            && strtotime((string) $this->end_time) > time();
    }

    public function isAcceptingBids(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && strtotime((string) $this->end_time) > time()
            && $this->hasStarted();
    }

    /**
     * @return list<self>
     */
    public static function findActive(?int $categoryId = null, ?string $search = null): array
    {
        $query = static::find()
            ->alias('a')
            ->select([
                'a.*',
                'category_name' => 'c.name',
                'total_bids' => new Expression('COUNT(b.bid_id)'),
            ])
            ->innerJoin(['c' => Category::tableName()], 'a.category_id = c.category_id')
            ->leftJoin(['b' => Bid::tableName()], 'a.auction_id = b.auction_id')
            ->where(['a.status' => self::STATUS_ACTIVE])
            ->andWhere(['>', 'a.end_time', new Expression('CURRENT_TIMESTAMP')])
            ->groupBy(['a.auction_id', 'c.name'])
            ->orderBy(['a.created_at' => SORT_DESC]);

        if ($categoryId) {
            $query->andWhere(['a.category_id' => $categoryId]);
        }

        if ($search !== null && $search !== '') {
            $query->andWhere([
                'or',
                ['ilike', 'a.title', $search],
                ['ilike', 'a.description', $search],
            ]);
        }

        return $query->all();
    }

    /**
     * Active auctions ending soon — for landing page featured listings.
     *
     * @return list<self>
     */
    public static function findEndingSoon(int $limit = 4): array
    {
        $limit = max(1, min($limit, 12));

        return static::find()
            ->alias('a')
            ->select([
                'a.*',
                'category_name' => 'c.name',
                'total_bids' => new Expression('COUNT(b.bid_id)'),
            ])
            ->innerJoin(['c' => Category::tableName()], 'a.category_id = c.category_id')
            ->leftJoin(['b' => Bid::tableName()], 'a.auction_id = b.auction_id')
            ->where(['a.status' => self::STATUS_ACTIVE])
            ->andWhere(['>', 'a.end_time', new Expression('CURRENT_TIMESTAMP')])
            ->andWhere(['<=', 'a.start_time', new Expression('CURRENT_TIMESTAMP')])
            ->groupBy(['a.auction_id', 'c.name'])
            ->orderBy(['a.end_time' => SORT_ASC])
            ->limit($limit)
            ->all();
    }

    /**
     * @return array{active: list<self>, timedOut: list<self>, closed: list<self>, cancelled: list<self>}
     */
    public static function findGroupedForAuctioneer(int $auctioneerId): array
    {
        $auctions = static::find()
            ->alias('a')
            ->select([
                'a.*',
                'category_name' => 'c.name',
                'total_bids' => new Expression('COUNT(b.bid_id)'),
                'final_price' => 'wb.bid_amount',
                'winner_first_name' => 'winner.first_name',
                'winner_last_name' => 'winner.last_name',
            ])
            ->innerJoin(['c' => Category::tableName()], 'a.category_id = c.category_id')
            ->leftJoin(['b' => Bid::tableName()], 'a.auction_id = b.auction_id')
            ->leftJoin(
                ['wb' => Bid::tableName()],
                'wb.auction_id = a.auction_id AND wb.is_winning = TRUE',
            )
            ->leftJoin(['winner' => User::tableName()], 'wb.bidder_id = winner.user_id')
            ->where(['a.auctioneer_id' => $auctioneerId])
            ->groupBy([
                'a.auction_id',
                'c.name',
                'wb.bid_amount',
                'winner.first_name',
                'winner.last_name',
            ])
            ->orderBy(['a.created_at' => SORT_DESC])
            ->all();

        $active = [];
        $timedOut = [];
        $closed = [];
        $cancelled = [];

        foreach ($auctions as $auction) {
            if ($auction->status === self::STATUS_ACTIVE && $auction->isTimedOut()) {
                $timedOut[] = $auction;
                continue;
            }

            match ($auction->status) {
                self::STATUS_ACTIVE => $active[] = $auction,
                self::STATUS_CLOSED => $closed[] = $auction,
                default => $cancelled[] = $auction,
            };
        }

        return compact('active', 'timedOut', 'closed', 'cancelled');
    }

    public function endEarly(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $this->end_time = date('Y-m-d H:i:s');

        return $this->closeAndFinalizeWinner();
    }

    /**
     * Close the auction if its end time has passed (auto-expire).
     */
    public function finalizeIfExpired(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if (strtotime((string) $this->end_time) > time()) {
            return false;
        }

        return $this->closeAndFinalizeWinner();
    }

    /**
     * Mark auction CLOSED, promote winning bid to WON, and notify parties.
     */
    private function closeAndFinalizeWinner(): bool
    {
        $db = static::getDb();
        $tx = $db->beginTransaction();

        try {
            $this->status = self::STATUS_CLOSED;

            if (!$this->save(false, ['status', 'end_time'])) {
                throw new \RuntimeException('Failed to close auction.');
            }

            /** @var Bid|null $winning */
            $winning = Bid::find()
                ->where([
                    'auction_id' => $this->auction_id,
                    'is_winning' => true,
                ])
                ->one();

            if ($winning !== null) {
                $winning->status = Bid::STATUS_WON;
                $winning->save(false, ['status']);

                $won = new Notification();
                $won->user_id = (int) $winning->bidder_id;
                $won->auction_id = (int) $this->auction_id;
                $won->type = Notification::TYPE_AUCTION_WON;
                $won->message = 'Congratulations! You won "' . $this->title . '" for '
                    . self::formatKes($winning->bid_amount)
                    . '. Open the auction to complete payment via M-Pesa.';
                $won->read_status = false;
                $won->save(false);
            }

            $closed = new Notification();
            $closed->user_id = (int) $this->auctioneer_id;
            $closed->auction_id = (int) $this->auction_id;
            $closed->type = Notification::TYPE_AUCTION_CLOSED;
            $closed->message = $winning !== null
                ? 'Your auction "' . $this->title . '" has ended. Winning bid: '
                    . self::formatKes($winning->bid_amount) . '.'
                : 'Your auction "' . $this->title . '" ended with no bids. You can extend it from the auction page.';
            $closed->read_status = false;
            $closed->save(false);

            $tx->commit();

            if ($winning !== null) {
                $winnerUser = User::findOne((int) $winning->bidder_id);
                if ($winnerUser !== null) {
                    MailService::auctionWon($winnerUser, $this, (float) $winning->bid_amount);
                }
            }

            $auctioneer = User::findOne((int) $this->auctioneer_id);
            if ($auctioneer !== null) {
                MailService::auctionClosed(
                    $auctioneer,
                    $this,
                    $winning !== null ? (float) $winning->bid_amount : null,
                );
            }

            return true;
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error($e->getMessage(), __METHOD__);

            throw $e;
        }
    }

    /**
     * Close all ACTIVE auctions whose end time has passed.
     */
    public static function finalizeAllExpired(): int
    {
        $ids = static::find()
            ->select(['auction_id'])
            ->where(['status' => self::STATUS_ACTIVE])
            ->andWhere(['<=', 'end_time', new Expression('CURRENT_TIMESTAMP')])
            ->column();

        $count = 0;
        foreach ($ids as $id) {
            $auction = static::findOne((int) $id);
            if ($auction === null) {
                continue;
            }
            try {
                if ($auction->finalizeIfExpired()) {
                    $count++;
                }
            } catch (\Throwable $e) {
                Yii::error($e->getMessage(), __METHOD__);
            }
        }

        return $count;
    }

    public static function formatKes(float|int|string|null $amount): string
    {
        return 'KES ' . number_format((float) $amount, 0, '.', ',');
    }

    public static function timeRemaining(?string $endTime): string
    {
        if ($endTime === null || $endTime === '') {
            return '—';
        }

        $diff = strtotime($endTime) - time();

        if ($diff <= 0) {
            return 'Ended';
        }

        $days = intdiv($diff, 86400);
        $hours = intdiv($diff % 86400, 3600);
        $mins = intdiv($diff % 3600, 60);

        if ($days > 0) {
            return "{$days}d {$hours}h left";
        }

        if ($hours > 0) {
            return "{$hours}h {$mins}m left";
        }

        return "{$mins}m left";
    }
}
