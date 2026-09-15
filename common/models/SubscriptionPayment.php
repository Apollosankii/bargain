<?php

declare(strict_types=1);

namespace common\models;

use common\services\MpesaService;
use DateTimeImmutable;
use Throwable;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * M-Pesa payment attempt for an auctioneer subscription.
 *
 * @property int $subscription_payment_id
 * @property int $user_id
 * @property string $plan
 * @property string $amount
 * @property string $phone
 * @property string $status PENDING|COMPLETED|FAILED|CANCELLED
 * @property string|null $merchant_request_id
 * @property string|null $checkout_request_id
 * @property string|null $mpesa_receipt
 * @property int|null $result_code
 * @property string|null $result_desc
 * @property string $created_at
 * @property string $updated_at
 *
 * @property-read User $user
 */
class SubscriptionPayment extends ActiveRecord
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public static function tableName(): string
    {
        return '{{%subscription_payment}}';
    }

    public static function primaryKey(): array
    {
        return ['subscription_payment_id'];
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
            [['user_id', 'plan', 'amount', 'phone'], 'required'],
            [['user_id', 'result_code'], 'integer'],
            [['amount'], 'number', 'min' => 1],
            [['plan'], 'in', 'range' => array_keys(Subscription::paidPlanOptions())],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['status'], 'in', 'range' => [
                self::STATUS_PENDING,
                self::STATUS_COMPLETED,
                self::STATUS_FAILED,
                self::STATUS_CANCELLED,
            ]],
            [['phone'], 'string', 'max' => 20],
            [['merchant_request_id', 'checkout_request_id'], 'string', 'max' => 100],
            [['mpesa_receipt'], 'string', 'max' => 50],
            [['result_desc'], 'string'],
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['user_id' => 'user_id']);
    }

    public static function planAmount(string $plan): ?int
    {
        $prices = Yii::$app->params['subscriptionPrices'] ?? [];

        if (!isset($prices[$plan])) {
            return null;
        }

        return (int) $prices[$plan];
    }

    public static function hasPendingForUser(int $userId): bool
    {
        return static::find()
            ->where([
                'user_id' => $userId,
                'status' => self::STATUS_PENDING,
            ])
            ->exists();
    }

    public static function findPendingForUser(int $userId): ?self
    {
        /** @var self|null $payment */
        $payment = static::find()
            ->where([
                'user_id' => $userId,
                'status' => self::STATUS_PENDING,
            ])
            ->orderBy(['subscription_payment_id' => SORT_DESC])
            ->one();

        return $payment;
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
            Yii::warning('M-Pesa callback missing stkCallback body.', __METHOD__);

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

    /**
     * Poll Daraja when the callback has not arrived yet.
     */
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
            // Request in progress — still waiting on the customer.
            return;
        }

        $this->completeFromStkResult([
            'ResultCode' => $resultCode,
            'ResultDesc' => (string) ($response['ResultDesc'] ?? ''),
            'CallbackMetadata' => null,
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

        $tx = Yii::$app->db->beginTransaction();

        try {
            $this->status = self::STATUS_COMPLETED;
            if (!$this->save(false)) {
                $tx->rollBack();

                return;
            }

            $subscription = Subscription::startForUser((int) $this->user_id, $this->plan);
            if ($subscription === null) {
                $tx->rollBack();
                Yii::error("Subscription activation failed for payment #{$this->subscription_payment_id}", __METHOD__);

                return;
            }

            $tx->commit();
        } catch (Throwable $e) {
            $tx->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
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
            $this->status = self::STATUS_CANCELLED;
            $this->result_desc = 'Payment request timed out.';
            $this->save(false);
        }
    }
}
