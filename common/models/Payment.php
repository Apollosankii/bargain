<?php

declare(strict_types=1);

namespace common\models;

use common\services\MailService;
use common\services\MpesaService;
use DateTimeImmutable;
use Throwable;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $payment_id
 * @property int $user_id
 * @property int $auction_id
 * @property string $amount
 * @property string $status PENDING|COMPLETED|FAILED|REFUNDED
 * @property string $method MPESA|CARD|BANK_TRANSFER
 * @property string|null $phone
 * @property string|null $merchant_request_id
 * @property string|null $checkout_request_id
 * @property string|null $mpesa_receipt
 * @property int|null $result_code
 * @property string|null $result_desc
 * @property string $created_at
 * @property string $updated_at
 *
 * @property-read User $user
 * @property-read Auction $auction
 */
class Payment extends ActiveRecord
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_REFUNDED = 'REFUNDED';

    public const METHOD_MPESA = 'MPESA';
    public const METHOD_CARD = 'CARD';
    public const METHOD_BANK_TRANSFER = 'BANK_TRANSFER';

    public static function tableName(): string
    {
        return '{{%payments}}';
    }

    public static function primaryKey(): array
    {
        return ['payment_id'];
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
            [['user_id', 'auction_id', 'amount', 'method'], 'required'],
            [['user_id', 'auction_id', 'result_code'], 'integer'],
            [['amount'], 'number', 'min' => 0.01],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['status'], 'in', 'range' => [
                self::STATUS_PENDING,
                self::STATUS_COMPLETED,
                self::STATUS_FAILED,
                self::STATUS_REFUNDED,
            ]],
            [['method'], 'default', 'value' => self::METHOD_MPESA],
            [['method'], 'in', 'range' => [
                self::METHOD_MPESA,
                self::METHOD_CARD,
                self::METHOD_BANK_TRANSFER,
            ]],
            [['phone'], 'string', 'max' => 20],
            [['merchant_request_id', 'checkout_request_id'], 'string', 'max' => 100],
            [['mpesa_receipt'], 'string', 'max' => 50],
            [['result_desc'], 'string'],
            [['user_id', 'auction_id'], 'unique', 'targetAttribute' => ['user_id', 'auction_id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'method' => 'Payment Method',
            'amount' => 'Amount (KES)',
            'status' => 'Status',
            'phone' => 'M-Pesa Phone',
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

    public static function methodOptions(): array
    {
        return [
            self::METHOD_MPESA => 'M-Pesa',
            self::METHOD_CARD => 'Card',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
        ];
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_COMPLETED => 'badge-success',
            self::STATUS_FAILED, self::STATUS_REFUNDED => 'badge-error',
            default => 'badge-warning',
        };
    }

    public static function findPendingForAuction(int $userId, int $auctionId): ?self
    {
        /** @var self|null $payment */
        $payment = static::find()
            ->where([
                'user_id' => $userId,
                'auction_id' => $auctionId,
                'status' => self::STATUS_PENDING,
            ])
            ->one();

        return $payment;
    }

    public function prepareForRetry(): void
    {
        $this->status = self::STATUS_PENDING;
        $this->merchant_request_id = null;
        $this->checkout_request_id = null;
        $this->mpesa_receipt = null;
        $this->result_code = null;
        $this->result_desc = null;
    }

    /**
     * @param array<string, mixed> $stkResponse
     */
    public function applyStkInitResponse(array $stkResponse): bool
    {
        $this->merchant_request_id = (string) ($stkResponse['MerchantRequestID'] ?? '');
        $this->checkout_request_id = (string) ($stkResponse['CheckoutRequestID'] ?? '');
        $responseCode = (string) ($stkResponse['ResponseCode'] ?? '');

        if ($responseCode !== '0' || $this->checkout_request_id === '') {
            $this->status = self::STATUS_FAILED;
            $this->result_desc = (string) ($stkResponse['ResponseDescription'] ?? 'STK push could not be initiated.');
        }

        return $this->save(false);
    }

    /**
     * @param array<string, mixed> $callbackBody
     */
    public static function handleStkCallback(array $callbackBody): void
    {
        $stkCallback = $callbackBody['Body']['stkCallback'] ?? null;
        if (!is_array($stkCallback)) {
            return;
        }

        $checkoutRequestId = (string) ($stkCallback['CheckoutRequestID'] ?? '');
        if ($checkoutRequestId === '') {
            return;
        }

        /** @var self|null $payment */
        $payment = static::find()
            ->where(['checkout_request_id' => $checkoutRequestId])
            ->one();

        if ($payment === null || $payment->status !== self::STATUS_PENDING) {
            return;
        }

        $payment->completeFromStkResult($stkCallback);
    }

    public function refreshFromQuery(): void
    {
        if ($this->status !== self::STATUS_PENDING || $this->checkout_request_id === null || $this->checkout_request_id === '') {
            return;
        }

        try {
            $mpesa = new MpesaService();
            $response = $mpesa->stkQuery($this->checkout_request_id);
        } catch (Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);

            return;
        }

        $resultCode = (int) ($response['ResultCode'] ?? -1);
        if ($resultCode === 1037) {
            return;
        }

        $this->completeFromStkResult([
            'ResultCode' => $resultCode,
            'ResultDesc' => (string) ($response['ResultDesc'] ?? ''),
            'CallbackMetadata' => $response['CallbackMetadata'] ?? null,
        ]);
    }

    /**
     * @param array<string, mixed> $stkResult
     */
    public function completeFromStkResult(array $stkResult): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            return;
        }

        $resultCode = (int) ($stkResult['ResultCode'] ?? -1);
        $this->result_code = $resultCode;
        $this->result_desc = (string) ($stkResult['ResultDesc'] ?? '');

        if ($resultCode !== 0) {
            $this->status = self::STATUS_FAILED;
            $this->save(false);

            return;
        }

        $metadata = $stkResult['CallbackMetadata']['Item'] ?? [];
        if (is_array($metadata)) {
            foreach ($metadata as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (($item['Name'] ?? '') === 'MpesaReceiptNumber') {
                    $this->mpesa_receipt = (string) ($item['Value'] ?? '');
                }
            }
        }

        $this->status = self::STATUS_COMPLETED;
        if (!$this->save(false)) {
            return;
        }

        $auction = $this->auction;
        if ($auction !== null) {
            Notification::notifySystem(
                (int) $auction->auctioneer_id,
                'A winning bidder paid ' . Auction::formatKes($this->amount) . ' for "' . $auction->title . '".',
                (int) $auction->auction_id,
            );

            $auctioneer = User::findOne((int) $auction->auctioneer_id);
            if ($auctioneer !== null) {
                MailService::paymentReceived($auctioneer, $this, $auction);
            }

            $bidder = User::findOne((int) $this->user_id);
            if ($bidder !== null) {
                MailService::paymentReceipt($bidder, $this, $auction);
            }
        }
    }

    public function isExpired(): bool
    {
        $created = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->created_at);
        if ($created === false) {
            return false;
        }

        return $created < new DateTimeImmutable('-15 minutes');
    }

    public function markExpiredIfNeeded(): void
    {
        if ($this->status === self::STATUS_PENDING && $this->isExpired()) {
            $this->status = self::STATUS_FAILED;
            $this->result_desc = 'Payment request timed out.';
            $this->save(false);
        }
    }
}
