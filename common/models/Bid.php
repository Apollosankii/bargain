<?php

declare(strict_types=1);

namespace common\models;

use common\services\MailService;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $bid_id
 * @property int $auction_id
 * @property int $bidder_id
 * @property string $bid_amount
 * @property string $bid_time
 * @property bool $is_winning
 * @property string $status WINNING|OUTBID|WON|WITHDRAWN
 *
 * @property-read Auction $auction
 * @property-read User $bidder
 */
class Bid extends ActiveRecord
{
    public const STATUS_WINNING = 'WINNING';
    public const STATUS_OUTBID = 'OUTBID';
    public const STATUS_WON = 'WON';
    public const STATUS_WITHDRAWN = 'WITHDRAWN';

    public $auction_title;
    public $image_url;
    public $current_bid;
    public $end_time;
    public $auction_status;
    public $category_name;
    public $bid_status;
    public $first_name;
    public $last_name;

    public static function tableName(): string
    {
        return '{{%bids}}';
    }

    public static function primaryKey(): array
    {
        return ['bid_id'];
    }

    public function rules(): array
    {
        return [
            [['auction_id', 'bidder_id', 'bid_amount'], 'required'],
            [['auction_id', 'bidder_id'], 'integer'],
            [['bid_amount'], 'number', 'min' => 0.01],
            [['is_winning'], 'boolean'],
            [['status'], 'in', 'range' => [
                self::STATUS_WINNING,
                self::STATUS_OUTBID,
                self::STATUS_WON,
                self::STATUS_WITHDRAWN,
            ]],
        ];
    }

    public function getAuction(): ActiveQuery
    {
        return $this->hasOne(Auction::class, ['auction_id' => 'auction_id']);
    }

    public function getBidder(): ActiveQuery
    {
        return $this->hasOne(User::class, ['user_id' => 'bidder_id']);
    }

    /**
     * Place a bid with outbid + notifications (transactional).
     *
     * @return array{ok:bool,message:string,bid?:self}
     */
    public static function placeBid(Auction $auction, User $bidder, float $amount): array
    {
        if (!$bidder->isBidder()) {
            return ['ok' => false, 'message' => 'Only bidders can place bids.'];
        }

        if ($auction->status !== Auction::STATUS_ACTIVE) {
            return ['ok' => false, 'message' => "This auction is {$auction->status} and no longer accepting bids."];
        }

        if (strtotime((string) $auction->end_time) < time()) {
            return ['ok' => false, 'message' => 'This auction has ended.'];
        }

        if ((int) $auction->auctioneer_id === (int) $bidder->user_id) {
            return ['ok' => false, 'message' => 'You cannot bid on your own auction.'];
        }

        $alreadyLeading = static::find()
            ->where([
                'auction_id' => $auction->auction_id,
                'bidder_id' => $bidder->user_id,
                'is_winning' => true,
            ])
            ->exists();

        if ($alreadyLeading) {
            return [
                'ok' => false,
                'message' => 'You are already the leading bidder. You can bid again if someone outbids you.',
            ];
        }

        if ($amount <= (float) $auction->current_bid) {
            return [
                'ok' => false,
                'message' => 'Your bid must be higher than the current bid of '
                    . Auction::formatKes($auction->current_bid),
            ];
        }

        $db = static::getDb();
        $tx = $db->beginTransaction();

        try {
            // Re-read auction row for concurrency
            $auction = Auction::findOne($auction->auction_id);

            if ($auction === null || !$auction->isAcceptingBids()) {
                throw new \RuntimeException('Auction is no longer accepting bids.');
            }

            if ($amount <= (float) $auction->current_bid) {
                throw new \RuntimeException(
                    'Your bid must be higher than the current bid of '
                    . Auction::formatKes($auction->current_bid),
                );
            }

            $prevWinning = static::find()
                ->alias('b')
                ->select(['b.bid_id', 'b.bidder_id', 'u.first_name'])
                ->innerJoin(['u' => User::tableName()], 'b.bidder_id = u.user_id')
                ->where(['b.auction_id' => $auction->auction_id, 'b.is_winning' => true])
                ->asArray()
                ->one();

            if ($prevWinning) {
                static::updateAll(
                    ['is_winning' => false, 'status' => self::STATUS_OUTBID],
                    ['auction_id' => $auction->auction_id, 'is_winning' => true],
                );
            }

            $bid = new static();
            $bid->auction_id = $auction->auction_id;
            $bid->bidder_id = $bidder->user_id;
            $bid->bid_amount = $amount;
            $bid->is_winning = true;
            $bid->status = self::STATUS_WINNING;
            $bid->bid_time = date('Y-m-d H:i:s');

            if (!$bid->save()) {
                throw new \RuntimeException('Could not save bid.');
            }

            $auction->current_bid = $amount;

            if (!$auction->save(false, ['current_bid'])) {
                throw new \RuntimeException('Could not update auction price.');
            }

            if ($prevWinning) {
                $outbid = new Notification();
                $outbid->user_id = (int) $prevWinning['bidder_id'];
                $outbid->auction_id = $auction->auction_id;
                $outbid->type = Notification::TYPE_OUTBID;
                $outbid->message = "Dear {$prevWinning['first_name']}, you have been outbid on \"{$auction->title}\". "
                    . 'The new highest bid is ' . Auction::formatKes($amount)
                    . '. Place a higher bid to stay in the running!';
                $outbid->read_status = false;
                $outbid->save(false);
            }

            $auctioneer = $auction->auctioneer;
            $notifAuctioneer = new Notification();
            $notifAuctioneer->user_id = (int) $auction->auctioneer_id;
            $notifAuctioneer->auction_id = $auction->auction_id;
            $notifAuctioneer->type = Notification::TYPE_BID_RECEIVED;
            $notifAuctioneer->message = 'Dear ' . ($auctioneer->first_name ?? 'Auctioneer')
                . ", {$bidder->first_name} {$bidder->last_name} placed a bid of "
                . Auction::formatKes($amount)
                . " on your auction \"{$auction->title}\".";
            $notifAuctioneer->read_status = false;
            $notifAuctioneer->save(false);

            $tx->commit();

            if ($prevWinning) {
                $outbidUser = User::findOne((int) $prevWinning['bidder_id']);
                if ($outbidUser !== null) {
                    MailService::outbid($outbidUser, $auction, $amount);
                }
            }

            if ($auctioneer instanceof User) {
                MailService::bidReceived($auctioneer, $auction, $bidder, $amount);
            }

            return [
                'ok' => true,
                'message' => 'Bid of ' . Auction::formatKes($amount) . ' placed successfully!',
                'bid' => $bid,
            ];
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error($e->getMessage(), __METHOD__);

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return list<self>
     */
    public static function findForBidder(int $bidderId): array
    {
        return static::find()
            ->alias('b')
            ->select([
                'b.*',
                'bid_status' => 'b.status',
                'auction_title' => 'a.title',
                'image_url' => 'a.image_url',
                'current_bid' => 'a.current_bid',
                'end_time' => 'a.end_time',
                'auction_status' => 'a.status',
                'category_name' => 'c.name',
            ])
            ->innerJoin(['a' => Auction::tableName()], 'b.auction_id = a.auction_id')
            ->innerJoin(['c' => Category::tableName()], 'a.category_id = c.category_id')
            ->where(['b.bidder_id' => $bidderId])
            ->orderBy(['b.bid_time' => SORT_DESC])
            ->all();
    }

    /**
     * @return list<self>
     */
    public static function findWatchlist(int $bidderId): array
    {
        return static::find()
            ->alias('b')
            ->select([
                'b.*',
                'bid_status' => 'b.status',
                'auction_title' => 'a.title',
                'image_url' => 'a.image_url',
                'current_bid' => 'a.current_bid',
                'end_time' => 'a.end_time',
                'category_name' => 'c.name',
            ])
            ->innerJoin(['a' => Auction::tableName()], 'b.auction_id = a.auction_id')
            ->innerJoin(['c' => Category::tableName()], 'a.category_id = c.category_id')
            ->where([
                'b.bidder_id' => $bidderId,
                'a.status' => Auction::STATUS_ACTIVE,
                'b.is_winning' => true,
            ])
            ->andWhere(['>', 'a.end_time', new Expression('CURRENT_TIMESTAMP')])
            ->orderBy(['a.end_time' => SORT_ASC])
            ->all();
    }

    /**
     * @return list<self>
     */
    public static function findByAuction(int $auctionId): array
    {
        return static::find()
            ->alias('b')
            ->select([
                'b.*',
                'first_name' => 'u.first_name',
                'last_name' => 'u.last_name',
            ])
            ->innerJoin(['u' => User::tableName()], 'b.bidder_id = u.user_id')
            ->where(['b.auction_id' => $auctionId])
            ->orderBy(['b.bid_amount' => SORT_DESC])
            ->all();
    }
}
