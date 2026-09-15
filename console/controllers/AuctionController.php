<?php

declare(strict_types=1);

namespace console\controllers;

use common\models\Auction;
use common\models\AuctionInterest;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Auction lifecycle jobs for Task Scheduler / cron.
 *
 * Recommended schedule (every minute):
 *   php yii auction/tick
 *
 * Or individually:
 *   php yii auction/finalize-expired
 *   php yii auction/notify-opened
 */
class AuctionController extends Controller
{
    /**
     * Close expired ACTIVE auctions: set CLOSED, mark winner WON, send notifications.
     * Cron: php yii auction/finalize-expired
     */
    public function actionFinalizeExpired(): int
    {
        $count = Auction::finalizeAllExpired();
        $this->stdout('Finalized ' . $count . " auction(s).\n");

        return ExitCode::OK;
    }

    /**
     * Notify bidders who marked interest when auctions open for bidding.
     * Cron: php yii auction/notify-opened
     */
    public function actionNotifyOpened(): int
    {
        $count = AuctionInterest::notifyOpenedAuctions();
        $this->stdout('Notified ' . $count . " interested bidder(s).\n");

        return ExitCode::OK;
    }

    /**
     * Single entry point for the minute cron: open notifications + finalize ended auctions.
     * Cron: php yii auction/tick
     */
    public function actionTick(): int
    {
        $opened = AuctionInterest::notifyOpenedAuctions();
        $closed = Auction::finalizeAllExpired();

        $this->stdout(
            date('Y-m-d H:i:s')
            . " — opened notices: {$opened}, finalized: {$closed}\n"
        );

        Yii::info(
            "Auction tick: opened={$opened}, finalized={$closed}",
            __METHOD__,
        );

        return ExitCode::OK;
    }
}
