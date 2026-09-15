<?php

declare(strict_types=1);

namespace common\models;

use DateInterval;
use DateTimeImmutable;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Auctioneer subscription (matches PostgreSQL `subscription` table).
 *
 * @property int $subscription_id
 * @property int $user_id
 * @property string $plan 1_MONTH|6_MONTHS|12_MONTHS|FREE_TRIAL
 * @property string $start_date
 * @property string $end_date
 * @property string $status ACTIVE|EXPIRED|CANCELLED
 *
 * @property-read User $user
 */
class Subscription extends ActiveRecord
{
    public const PLAN_1_MONTH = '1_MONTH';
    public const PLAN_6_MONTHS = '6_MONTHS';
    public const PLAN_12_MONTHS = '12_MONTHS';
    public const PLAN_FREE_TRIAL = 'FREE_TRIAL';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public static function tableName(): string
    {
        return '{{%subscription}}';
    }

    public static function primaryKey(): array
    {
        return ['subscription_id'];
    }

    public function rules(): array
    {
        return [
            [['user_id', 'plan', 'start_date', 'end_date'], 'required'],
            [['user_id'], 'integer'],
            [['plan'], 'in', 'range' => array_keys(self::planOptions())],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['status'], 'in', 'range' => [
                self::STATUS_ACTIVE,
                self::STATUS_EXPIRED,
                self::STATUS_CANCELLED,
            ]],
            [['start_date', 'end_date'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'plan' => 'Plan',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'status' => 'Status',
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['user_id' => 'user_id']);
    }

    /**
     * @return array<string, string>
     */
    public static function paidPlanOptions(): array
    {
        return array_diff_key(self::planOptions(), [self::PLAN_FREE_TRIAL => true]);
    }

    public static function isPaidPlan(string $plan): bool
    {
        return isset(self::paidPlanOptions()[$plan]);
    }

    /**
     * @return array<string, string>
     */
    public static function planOptions(): array
    {
        return [
            self::PLAN_FREE_TRIAL => '7-Day Free Trial',
            self::PLAN_1_MONTH => '1 Month',
            self::PLAN_6_MONTHS => '6 Months',
            self::PLAN_12_MONTHS => '12 Months',
        ];
    }

    public static function planDuration(string $plan): DateInterval
    {
        return match ($plan) {
            self::PLAN_FREE_TRIAL => new DateInterval('P7D'),
            self::PLAN_1_MONTH => new DateInterval('P1M'),
            self::PLAN_6_MONTHS => new DateInterval('P6M'),
            self::PLAN_12_MONTHS => new DateInterval('P12M'),
            default => throw new \InvalidArgumentException("Unknown plan: {$plan}"),
        };
    }

    public static function isActiveFor(int $userId): bool
    {
        return static::find()
            ->where([
                'user_id' => $userId,
                'status' => self::STATUS_ACTIVE,
            ])
            ->andWhere(['>', 'end_date', new Expression('CURRENT_TIMESTAMP')])
            ->exists();
    }

    public static function canStartTrial(int $userId): bool
    {
        return !static::find()
            ->where(['user_id' => $userId])
            ->exists();
    }

    /**
     * Activates a plan immediately. Cancels any existing ACTIVE rows for the user.
     */
    public static function startForUser(int $userId, string $plan): ?self
    {
        if (!isset(self::planOptions()[$plan])) {
            return null;
        }

        if ($plan === self::PLAN_FREE_TRIAL && !self::canStartTrial($userId)) {
            return null;
        }

        $tx = Yii::$app->db->beginTransaction();

        try {
            static::updateAll(
                ['status' => self::STATUS_CANCELLED],
                [
                    'user_id' => $userId,
                    'status' => self::STATUS_ACTIVE,
                ],
            );

            $start = new DateTimeImmutable('now');
            $end = $start->add(self::planDuration($plan));

            $subscription = new self();
            $subscription->user_id = $userId;
            $subscription->plan = $plan;
            $subscription->start_date = $start->format('Y-m-d H:i:s');
            $subscription->end_date = $end->format('Y-m-d H:i:s');
            $subscription->status = self::STATUS_ACTIVE;

            if (!$subscription->save()) {
                $tx->rollBack();

                return null;
            }

            $tx->commit();

            return $subscription;
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error($e->getMessage(), __METHOD__);

            return null;
        }
    }
}
