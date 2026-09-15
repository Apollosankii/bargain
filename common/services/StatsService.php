<?php

declare(strict_types=1);

namespace common\services;

use common\models\Auction;
use common\models\Bid;
use common\models\Payment;
use common\models\Subscription;
use common\models\SubscriptionPayment;
use Yii;
use yii\db\Expression;
use yii\db\Query;

class StatsService
{
    /**
     * @return array<string, mixed>
     */
    public static function forAuctioneer(int $auctioneerId, string $range = '30'): array
    {
        $auctionBase = (new Query())
            ->from(['a' => Auction::tableName()])
            ->where(['a.auctioneer_id' => $auctioneerId]);

        $active = (int) (clone $auctionBase)->andWhere(['a.status' => Auction::STATUS_ACTIVE])->count('*', Yii::$app->db);
        $closed = (int) (clone $auctionBase)->andWhere(['a.status' => Auction::STATUS_CLOSED])->count('*', Yii::$app->db);
        $cancelled = (int) (clone $auctionBase)->andWhere(['a.status' => Auction::STATUS_CANCELLED])->count('*', Yii::$app->db);
        $totalAuctions = $active + $closed + $cancelled;

        $endingSoon = (int) (clone $auctionBase)
            ->andWhere(['a.status' => Auction::STATUS_ACTIVE])
            ->andWhere(['>', 'a.end_time', new Expression('CURRENT_TIMESTAMP')])
            ->andWhere(['<=', 'a.end_time', new Expression("CURRENT_TIMESTAMP + INTERVAL '24 hours'")])
            ->count('*', Yii::$app->db);

        $totalBids = (int) (new Query())
            ->from(['b' => Bid::tableName()])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = b.auction_id')
            ->where(['a.auctioneer_id' => $auctioneerId])
            ->count('*', Yii::$app->db);

        $bidsLast7 = (int) (new Query())
            ->from(['b' => Bid::tableName()])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = b.auction_id')
            ->where(['a.auctioneer_id' => $auctioneerId])
            ->andWhere(['>=', 'b.bid_time', new Expression("CURRENT_TIMESTAMP - INTERVAL '7 days'")])
            ->count('*', Yii::$app->db);

        $bidsLast30 = (int) (new Query())
            ->from(['b' => Bid::tableName()])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = b.auction_id')
            ->where(['a.auctioneer_id' => $auctioneerId])
            ->andWhere(['>=', 'b.bid_time', new Expression("CURRENT_TIMESTAMP - INTERVAL '30 days'")])
            ->count('*', Yii::$app->db);

        $uniqueBidders = (int) (new Query())
            ->from(['b' => Bid::tableName()])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = b.auction_id')
            ->where(['a.auctioneer_id' => $auctioneerId])
            ->select(new Expression('COUNT(DISTINCT b.bidder_id)'))
            ->scalar(Yii::$app->db);

        $auctionsWithBids = (int) (new Query())
            ->from(['a' => Auction::tableName()])
            ->where(['a.auctioneer_id' => $auctioneerId])
            ->andWhere([
                'exists',
                (new Query())
                    ->from(['b' => Bid::tableName()])
                    ->where('b.auction_id = a.auction_id'),
            ])
            ->count('*', Yii::$app->db);

        $winningTotal = (float) (new Query())
            ->from(['b' => Bid::tableName()])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = b.auction_id')
            ->where([
                'a.auctioneer_id' => $auctioneerId,
                'a.status' => Auction::STATUS_CLOSED,
                'b.is_winning' => true,
            ])
            ->sum('b.bid_amount', Yii::$app->db);

        $avgFinal = (float) (new Query())
            ->from(['b' => Bid::tableName()])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = b.auction_id')
            ->where([
                'a.auctioneer_id' => $auctioneerId,
                'a.status' => Auction::STATUS_CLOSED,
                'b.is_winning' => true,
            ])
            ->average('b.bid_amount', Yii::$app->db);

        $paidRevenue = (float) (new Query())
            ->from(['p' => Payment::tableName()])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = p.auction_id')
            ->where([
                'a.auctioneer_id' => $auctioneerId,
                'p.status' => Payment::STATUS_COMPLETED,
            ])
            ->sum('p.amount', Yii::$app->db);

        $pendingPayments = (int) (new Query())
            ->from(['p' => Payment::tableName()])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = p.auction_id')
            ->where([
                'a.auctioneer_id' => $auctioneerId,
                'p.status' => Payment::STATUS_PENDING,
            ])
            ->count('*', Yii::$app->db);

        $failedPayments = (int) (new Query())
            ->from(['p' => Payment::tableName()])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = p.auction_id')
            ->where([
                'a.auctioneer_id' => $auctioneerId,
                'p.status' => Payment::STATUS_FAILED,
            ])
            ->count('*', Yii::$app->db);

        $completedPayments = (int) (new Query())
            ->from(['p' => Payment::tableName()])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = p.auction_id')
            ->where([
                'a.auctioneer_id' => $auctioneerId,
                'p.status' => Payment::STATUS_COMPLETED,
            ])
            ->count('*', Yii::$app->db);

        $byCategory = (new Query())
            ->select([
                'category' => 'c.name',
                'total' => new Expression('COUNT(a.auction_id)'),
            ])
            ->from(['a' => Auction::tableName()])
            ->innerJoin(['c' => '{{%categories}}'], 'c.category_id = a.category_id')
            ->where(['a.auctioneer_id' => $auctioneerId])
            ->groupBy(['c.name'])
            ->orderBy(['total' => SORT_DESC])
            ->all(Yii::$app->db);

        /** @var Subscription|null $subscription */
        $subscription = Subscription::find()
            ->where([
                'user_id' => $auctioneerId,
                'status' => Subscription::STATUS_ACTIVE,
            ])
            ->andWhere(['>', 'end_date', new Expression('CURRENT_TIMESTAMP')])
            ->orderBy(['end_date' => SORT_DESC])
            ->one();

        $subscriptionSpend = (float) (new Query())
            ->from(SubscriptionPayment::tableName())
            ->where([
                'user_id' => $auctioneerId,
                'status' => SubscriptionPayment::STATUS_COMPLETED,
            ])
            ->sum('amount', Yii::$app->db);

        $daysLeft = null;
        if ($subscription !== null) {
            $end = strtotime((string) $subscription->end_date);
            $daysLeft = max(0, (int) ceil(($end - time()) / 86400));
        }

        return [
            'totalAuctions' => $totalAuctions,
            'active' => $active,
            'closed' => $closed,
            'cancelled' => $cancelled,
            'endingSoon' => $endingSoon,
            'totalBids' => $totalBids,
            'bidsLast7' => $bidsLast7,
            'bidsLast30' => $bidsLast30,
            'uniqueBidders' => $uniqueBidders,
            'auctionsWithBids' => $auctionsWithBids,
            'zeroBidAuctions' => max(0, $totalAuctions - $auctionsWithBids),
            'expectedEarnings' => $winningTotal ?: 0.0,
            'avgFinalPrice' => $avgFinal ?: 0.0,
            'paidRevenue' => $paidRevenue ?: 0.0,
            'pendingPayments' => $pendingPayments,
            'failedPayments' => $failedPayments,
            'completedPayments' => $completedPayments,
            'byCategory' => $byCategory,
            'subscriptionPlan' => $subscription?->plan,
            'subscriptionDaysLeft' => $daysLeft,
            'subscriptionSpend' => $subscriptionSpend ?: 0.0,
            'chartRange' => self::normalizeRange($range),
            'charts' => self::auctioneerCharts($auctioneerId, $range),
        ];
    }

    public static function normalizeRange(string $range): string
    {
        return in_array($range, ['7', '30', '90', 'all'], true) ? $range : '30';
    }

    /**
     * @return array<string, mixed>
     */
    private static function auctioneerCharts(int $auctioneerId, string $range): array
    {
        $range = self::normalizeRange($range);
        $bucket = $range === 'all' ? 'month' : 'day';
        $periods = self::periodKeys($range);

        $bids = self::fillSeries($periods, self::queryDailyCounts(
            $auctioneerId,
            Bid::tableName(),
            'b.bid_time',
            $range,
            $bucket,
        ));

        $bidders = self::fillSeries($periods, self::queryDailyDistinct(
            $auctioneerId,
            'b.bidder_id',
            'b.bid_time',
            $range,
            $bucket,
        ));

        $listings = self::fillSeries($periods, self::queryAuctionCounts($auctioneerId, $range, $bucket));

        $revenue = self::fillSeries($periods, self::queryRevenueByPeriod($auctioneerId, $range, $bucket));

        return [
            'bucket' => $bucket,
            'labels' => array_map(static fn (string $key) => self::formatPeriodLabel($key, $bucket), $periods),
            'bids' => $bids,
            'bidders' => $bidders,
            'listings' => $listings,
            'revenue' => $revenue,
            'bidTotal' => array_sum($bids),
            'revenueTotal' => array_sum($revenue),
            'listingTotal' => array_sum($listings),
        ];
    }

    /**
     * @return list<string>
     */
    private static function periodKeys(string $range): array
    {
        $keys = [];

        if ($range === 'all') {
            $cursor = new \DateTimeImmutable('first day of this month');
            for ($i = 11; $i >= 0; $i--) {
                $keys[] = $cursor->modify("-{$i} months")->format('Y-m');
            }

            return $keys;
        }

        $days = (int) $range;
        $cursor = new \DateTimeImmutable('today');
        for ($i = $days - 1; $i >= 0; $i--) {
            $keys[] = $cursor->modify("-{$i} days")->format('Y-m-d');
        }

        return $keys;
    }

    private static function formatPeriodLabel(string $key, string $bucket): string
    {
        if ($bucket === 'month') {
            $dt = \DateTimeImmutable::createFromFormat('Y-m', $key);

            return $dt ? $dt->format('M Y') : $key;
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $key);

        return $dt ? $dt->format('M j') : $key;
    }

    /**
     * @param list<string> $periods
     * @param array<string, float|int> $map
     * @return list<float>
     */
    private static function fillSeries(array $periods, array $map): array
    {
        $values = [];
        foreach ($periods as $key) {
            $values[] = (float) ($map[$key] ?? 0);
        }

        return $values;
    }

    private static function rangeStart(string $range): string
    {
        if ($range === 'all') {
            return (new \DateTimeImmutable('first day of this month'))
                ->modify('-11 months')
                ->format('Y-m-d 00:00:00');
        }

        $days = (int) $range;

        return (new \DateTimeImmutable('today'))
            ->modify('-' . ($days - 1) . ' days')
            ->format('Y-m-d 00:00:00');
    }

    private static function periodSql(string $column, string $bucket): string
    {
        if ($bucket === 'month') {
            return "to_char(date_trunc('month', {$column}), 'YYYY-MM')";
        }

        return "to_char({$column}::date, 'YYYY-MM-DD')";
    }

    /**
     * @return array<string, float>
     */
    private static function queryDailyCounts(
        int $auctioneerId,
        string $table,
        string $timeColumn,
        string $range,
        string $bucket,
    ): array {
        $sql = self::periodSql($timeColumn, $bucket);
        $rows = (new Query())
            ->select([
                'period' => new Expression($sql),
                'total' => new Expression('COUNT(*)'),
            ])
            ->from(['b' => $table])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = b.auction_id')
            ->where(['a.auctioneer_id' => $auctioneerId])
            ->andWhere(['>=', $timeColumn, self::rangeStart($range)])
            ->groupBy(new Expression($sql))
            ->all(Yii::$app->db);

        return self::rowsToMap($rows);
    }

    /**
     * @return array<string, float>
     */
    private static function queryDailyDistinct(
        int $auctioneerId,
        string $distinctColumn,
        string $timeColumn,
        string $range,
        string $bucket,
    ): array {
        $sql = self::periodSql($timeColumn, $bucket);
        $rows = (new Query())
            ->select([
                'period' => new Expression($sql),
                'total' => new Expression("COUNT(DISTINCT {$distinctColumn})"),
            ])
            ->from(['b' => Bid::tableName()])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = b.auction_id')
            ->where(['a.auctioneer_id' => $auctioneerId])
            ->andWhere(['>=', $timeColumn, self::rangeStart($range)])
            ->groupBy(new Expression($sql))
            ->all(Yii::$app->db);

        return self::rowsToMap($rows);
    }

    /**
     * @return array<string, float>
     */
    private static function queryAuctionCounts(int $auctioneerId, string $range, string $bucket): array
    {
        $sql = self::periodSql('a.created_at', $bucket);
        $rows = (new Query())
            ->select([
                'period' => new Expression($sql),
                'total' => new Expression('COUNT(*)'),
            ])
            ->from(['a' => Auction::tableName()])
            ->where(['a.auctioneer_id' => $auctioneerId])
            ->andWhere(['>=', 'a.created_at', self::rangeStart($range)])
            ->groupBy(new Expression($sql))
            ->all(Yii::$app->db);

        return self::rowsToMap($rows);
    }

    /**
     * @return array<string, float>
     */
    private static function queryRevenueByPeriod(int $auctioneerId, string $range, string $bucket): array
    {
        $sql = self::periodSql('p.created_at', $bucket);
        $rows = (new Query())
            ->select([
                'period' => new Expression($sql),
                'total' => new Expression('COALESCE(SUM(p.amount), 0)'),
            ])
            ->from(['p' => Payment::tableName()])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = p.auction_id')
            ->where([
                'a.auctioneer_id' => $auctioneerId,
                'p.status' => Payment::STATUS_COMPLETED,
            ])
            ->andWhere(['>=', 'p.created_at', self::rangeStart($range)])
            ->groupBy(new Expression($sql))
            ->all(Yii::$app->db);

        return self::rowsToMap($rows);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<string, float>
     */
    private static function rowsToMap(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['period']] = (float) $row['total'];
        }

        return $map;
    }

    /**
     * @return array<string, mixed>
     */
    public static function forBidder(int $bidderId, string $range = '30'): array
    {
        $totalBids = (int) Bid::find()->where(['bidder_id' => $bidderId])->count();

        $auctionsBidOn = (int) (new Query())
            ->from(Bid::tableName())
            ->where(['bidder_id' => $bidderId])
            ->select(new Expression('COUNT(DISTINCT auction_id)'))
            ->scalar(Yii::$app->db);

        $winning = (int) Bid::find()
            ->where(['bidder_id' => $bidderId, 'status' => Bid::STATUS_WINNING])
            ->count();

        $outbid = (int) Bid::find()
            ->where(['bidder_id' => $bidderId, 'status' => Bid::STATUS_OUTBID])
            ->count();

        $won = (int) Bid::find()
            ->where(['bidder_id' => $bidderId, 'status' => Bid::STATUS_WON])
            ->count();

        $withdrawn = (int) Bid::find()
            ->where(['bidder_id' => $bidderId, 'status' => Bid::STATUS_WITHDRAWN])
            ->count();

        $closedLosses = (int) (new Query())
            ->from(['b' => Bid::tableName()])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = b.auction_id')
            ->where([
                'b.bidder_id' => $bidderId,
                'a.status' => Auction::STATUS_CLOSED,
            ])
            ->andWhere(['!=', 'b.status', Bid::STATUS_WON])
            ->select(new Expression('COUNT(DISTINCT b.auction_id)'))
            ->scalar(Yii::$app->db);

        $decided = $won + $closedLosses;
        $winRate = $decided > 0 ? round(($won / $decided) * 100, 1) : 0.0;

        $committed = (float) Bid::find()
            ->where(['bidder_id' => $bidderId, 'status' => Bid::STATUS_WINNING])
            ->sum('bid_amount');

        $bidsLast7 = (int) Bid::find()
            ->where(['bidder_id' => $bidderId])
            ->andWhere(['>=', 'bid_time', new Expression("CURRENT_TIMESTAMP - INTERVAL '7 days'")])
            ->count();

        $bidsLast30 = (int) Bid::find()
            ->where(['bidder_id' => $bidderId])
            ->andWhere(['>=', 'bid_time', new Expression("CURRENT_TIMESTAMP - INTERVAL '30 days'")])
            ->count();

        $watchlist = (int) (new Query())
            ->from(['b' => Bid::tableName()])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = b.auction_id')
            ->where([
                'b.bidder_id' => $bidderId,
                'b.status' => Bid::STATUS_WINNING,
                'a.status' => Auction::STATUS_ACTIVE,
            ])
            ->count('*', Yii::$app->db);

        $paidTotal = (float) Payment::find()
            ->where(['user_id' => $bidderId, 'status' => Payment::STATUS_COMPLETED])
            ->sum('amount');

        $pendingPayments = (int) Payment::find()
            ->where(['user_id' => $bidderId, 'status' => Payment::STATUS_PENDING])
            ->count();

        $failedPayments = (int) Payment::find()
            ->where(['user_id' => $bidderId, 'status' => Payment::STATUS_FAILED])
            ->count();

        $completedPayments = (int) Payment::find()
            ->where(['user_id' => $bidderId, 'status' => Payment::STATUS_COMPLETED])
            ->count();

        $byCategory = (new Query())
            ->select([
                'category' => 'c.name',
                'total' => new Expression('COUNT(DISTINCT b.auction_id)'),
            ])
            ->from(['b' => Bid::tableName()])
            ->innerJoin(['a' => Auction::tableName()], 'a.auction_id = b.auction_id')
            ->innerJoin(['c' => '{{%categories}}'], 'c.category_id = a.category_id')
            ->where(['b.bidder_id' => $bidderId])
            ->groupBy(['c.name'])
            ->orderBy(['total' => SORT_DESC])
            ->limit(8)
            ->all(Yii::$app->db);

        $highestBid = (float) Bid::find()
            ->where(['bidder_id' => $bidderId])
            ->max('bid_amount');

        return [
            'totalBids' => $totalBids,
            'auctionsBidOn' => $auctionsBidOn,
            'winning' => $winning,
            'outbid' => $outbid,
            'won' => $won,
            'withdrawn' => $withdrawn,
            'winRate' => $winRate,
            'committed' => $committed ?: 0.0,
            'bidsLast7' => $bidsLast7,
            'bidsLast30' => $bidsLast30,
            'watchlist' => $watchlist,
            'paidTotal' => $paidTotal ?: 0.0,
            'pendingPayments' => $pendingPayments,
            'failedPayments' => $failedPayments,
            'completedPayments' => $completedPayments,
            'byCategory' => $byCategory,
            'highestBid' => $highestBid ?: 0.0,
            'chartRange' => self::normalizeRange($range),
            'charts' => self::bidderCharts($bidderId, $range),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function bidderCharts(int $bidderId, string $range): array
    {
        $range = self::normalizeRange($range);
        $bucket = $range === 'all' ? 'month' : 'day';
        $periods = self::periodKeys($range);

        $bids = self::fillSeries($periods, self::queryBidderBidCounts($bidderId, $range, $bucket));
        $auctions = self::fillSeries($periods, self::queryBidderDistinctAuctions($bidderId, $range, $bucket));
        $revenue = self::fillSeries($periods, self::queryBidderPayments($bidderId, $range, $bucket));
        $spend = self::fillSeries($periods, self::queryBidderBidAmounts($bidderId, $range, $bucket));

        return [
            'bucket' => $bucket,
            'labels' => array_map(static fn (string $key) => self::formatPeriodLabel($key, $bucket), $periods),
            'bids' => $bids,
            'auctions' => $auctions,
            'revenue' => $revenue,
            'listings' => $spend,
            'bidTotal' => array_sum($bids),
            'revenueTotal' => array_sum($revenue),
            'listingTotal' => array_sum($spend),
        ];
    }

    /**
     * @return array<string, float>
     */
    private static function queryBidderBidCounts(int $bidderId, string $range, string $bucket): array
    {
        $sql = self::periodSql('b.bid_time', $bucket);
        $rows = (new Query())
            ->select([
                'period' => new Expression($sql),
                'total' => new Expression('COUNT(*)'),
            ])
            ->from(['b' => Bid::tableName()])
            ->where(['b.bidder_id' => $bidderId])
            ->andWhere(['>=', 'b.bid_time', self::rangeStart($range)])
            ->groupBy(new Expression($sql))
            ->all(Yii::$app->db);

        return self::rowsToMap($rows);
    }

    /**
     * @return array<string, float>
     */
    private static function queryBidderDistinctAuctions(int $bidderId, string $range, string $bucket): array
    {
        $sql = self::periodSql('b.bid_time', $bucket);
        $rows = (new Query())
            ->select([
                'period' => new Expression($sql),
                'total' => new Expression('COUNT(DISTINCT b.auction_id)'),
            ])
            ->from(['b' => Bid::tableName()])
            ->where(['b.bidder_id' => $bidderId])
            ->andWhere(['>=', 'b.bid_time', self::rangeStart($range)])
            ->groupBy(new Expression($sql))
            ->all(Yii::$app->db);

        return self::rowsToMap($rows);
    }

    /**
     * @return array<string, float>
     */
    private static function queryBidderPayments(int $bidderId, string $range, string $bucket): array
    {
        $sql = self::periodSql('p.created_at', $bucket);
        $rows = (new Query())
            ->select([
                'period' => new Expression($sql),
                'total' => new Expression('COALESCE(SUM(p.amount), 0)'),
            ])
            ->from(['p' => Payment::tableName()])
            ->where([
                'p.user_id' => $bidderId,
                'p.status' => Payment::STATUS_COMPLETED,
            ])
            ->andWhere(['>=', 'p.created_at', self::rangeStart($range)])
            ->groupBy(new Expression($sql))
            ->all(Yii::$app->db);

        return self::rowsToMap($rows);
    }

    /**
     * @return array<string, float>
     */
    private static function queryBidderBidAmounts(int $bidderId, string $range, string $bucket): array
    {
        $sql = self::periodSql('b.bid_time', $bucket);
        $rows = (new Query())
            ->select([
                'period' => new Expression($sql),
                'total' => new Expression('COALESCE(SUM(b.bid_amount), 0)'),
            ])
            ->from(['b' => Bid::tableName()])
            ->where(['b.bidder_id' => $bidderId])
            ->andWhere(['>=', 'b.bid_time', self::rangeStart($range)])
            ->groupBy(new Expression($sql))
            ->all(Yii::$app->db);

        return self::rowsToMap($rows);
    }
}
