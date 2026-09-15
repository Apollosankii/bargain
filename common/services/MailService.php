<?php

declare(strict_types=1);

namespace common\services;

use common\models\Auction;
use common\models\Payment;
use common\models\User;
use Throwable;
use Yii;
use yii\mail\MailerInterface;

/**
 * Transactional email helper for Bargain.
 * Failures are logged and never break the main flow (bids, payments, cron).
 */
class MailService
{
    public static function welcome(User $user): bool
    {
        $roleLabel = $user->isAuctioneer() ? 'auctioneer' : 'bidder';

        return self::sendToUser(
            $user,
            'Welcome to Bargain',
            'welcome',
            [
                'user' => $user,
                'roleLabel' => $roleLabel,
                'loginUrl' => self::absoluteUrl(['/site/login']),
            ],
        );
    }

    public static function auctionCreated(User $auctioneer, Auction $auction): bool
    {
        return self::sendToUser(
            $auctioneer,
            'Auction created: ' . $auction->title,
            'auctionCreated',
            [
                'user' => $auctioneer,
                'auction' => $auction,
                'auctionUrl' => self::absoluteUrl(['/auction/view', 'id' => $auction->auction_id]),
            ],
        );
    }

    public static function bidReceived(User $auctioneer, Auction $auction, User $bidder, float $amount): bool
    {
        return self::sendToUser(
            $auctioneer,
            'New bid on "' . $auction->title . '"',
            'bidReceived',
            [
                'user' => $auctioneer,
                'auction' => $auction,
                'bidder' => $bidder,
                'amountLabel' => Auction::formatKes($amount),
                'auctionUrl' => self::absoluteUrl(['/auction/view', 'id' => $auction->auction_id]),
            ],
        );
    }

    public static function outbid(User $bidder, Auction $auction, float $newAmount): bool
    {
        return self::sendToUser(
            $bidder,
            'You have been outbid on "' . $auction->title . '"',
            'outbid',
            [
                'user' => $bidder,
                'auction' => $auction,
                'amountLabel' => Auction::formatKes($newAmount),
                'auctionUrl' => self::absoluteUrl(['/auction/view', 'id' => $auction->auction_id]),
            ],
        );
    }

    public static function auctionWon(User $bidder, Auction $auction, float $amount): bool
    {
        return self::sendToUser(
            $bidder,
            'You won "' . $auction->title . '"',
            'auctionWon',
            [
                'user' => $bidder,
                'auction' => $auction,
                'amountLabel' => Auction::formatKes($amount),
                'auctionUrl' => self::absoluteUrl(['/auction/view', 'id' => $auction->auction_id]),
            ],
        );
    }

    public static function auctionClosed(User $auctioneer, Auction $auction, ?float $winningAmount): bool
    {
        $subject = $winningAmount !== null
            ? 'Auction ended: "' . $auction->title . '" has a winner'
            : 'Auction ended: "' . $auction->title . '" had no bids';

        return self::sendToUser(
            $auctioneer,
            $subject,
            'auctionClosed',
            [
                'user' => $auctioneer,
                'auction' => $auction,
                'winningAmountLabel' => $winningAmount !== null ? Auction::formatKes($winningAmount) : null,
                'auctionUrl' => self::absoluteUrl(['/auction/view', 'id' => $auction->auction_id]),
            ],
        );
    }

    public static function paymentReceived(User $auctioneer, Payment $payment, Auction $auction): bool
    {
        return self::sendToUser(
            $auctioneer,
            'Payment received for "' . $auction->title . '"',
            'paymentReceived',
            [
                'user' => $auctioneer,
                'auction' => $auction,
                'payment' => $payment,
                'amountLabel' => Auction::formatKes($payment->amount),
                'auctionUrl' => self::absoluteUrl(['/auction/view', 'id' => $auction->auction_id]),
            ],
        );
    }

    public static function paymentReceipt(User $bidder, Payment $payment, Auction $auction): bool
    {
        return self::sendToUser(
            $bidder,
            'Payment receipt for "' . $auction->title . '"',
            'paymentReceipt',
            [
                'user' => $bidder,
                'auction' => $auction,
                'payment' => $payment,
                'amountLabel' => Auction::formatKes($payment->amount),
                'paymentUrl' => self::absoluteUrl(['/payment/view', 'id' => $payment->payment_id]),
            ],
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function sendToUser(User $user, string $subject, string $view, array $params = []): bool
    {
        $email = trim((string) $user->email);
        if ($email === '') {
            return false;
        }

        return self::send($email, $subject, $view, $params);
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function send(string $to, string $subject, string $view, array $params = []): bool
    {
        try {
            /** @var MailerInterface $mailer */
            $mailer = Yii::$app->mailer;
            $fromEmail = (string) (Yii::$app->params['senderEmail'] ?? Yii::$app->params['supportEmail'] ?? 'noreply@bargain.local');
            $fromName = (string) (Yii::$app->params['senderName'] ?? (Yii::$app->name ?: 'Bargain'));

            return (bool) $mailer
                ->compose(
                    ['html' => $view . '-html', 'text' => $view . '-text'],
                    $params,
                )
                ->setFrom([$fromEmail => $fromName])
                ->setTo($to)
                ->setSubject($subject)
                ->send();
        } catch (Throwable $e) {
            Yii::error('Mail send failed [' . $view . ']: ' . $e->getMessage(), __METHOD__);

            return false;
        }
    }

    /**
     * @param array<string, mixed>|string $route
     */
    public static function absoluteUrl(array|string $route): string
    {
        try {
            if (Yii::$app instanceof \yii\web\Application) {
                return Yii::$app->urlManager->createAbsoluteUrl($route);
            }
        } catch (Throwable $e) {
            // Fall through.
        }

        $base = rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? 'http://localhost:3000'), '/');

        try {
            return $base . Yii::$app->urlManager->createUrl($route);
        } catch (Throwable $e) {
            if (is_string($route)) {
                return $base . '/' . ltrim($route, '/');
            }

            $path = ltrim((string) ($route[0] ?? ''), '/');
            $query = $route;
            unset($query[0]);

            return $base . '/' . $path . ($query !== [] ? ('?' . http_build_query($query)) : '');
        }
    }
}
