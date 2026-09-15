<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Tracks M-Pesa STK payments for auctioneer subscriptions.
 */
class m260720_080000_create_subscription_payment_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%subscription_payment}}', [
            'subscription_payment_id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'plan' => $this->string(20)->notNull(),
            'amount' => $this->decimal(10, 2)->notNull(),
            'phone' => $this->string(20)->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('PENDING'),
            'merchant_request_id' => $this->string(100),
            'checkout_request_id' => $this->string(100),
            'mpesa_receipt' => $this->string(50),
            'result_code' => $this->integer(),
            'result_desc' => $this->text(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex(
            'idx-subscription_payment-user_id',
            '{{%subscription_payment}}',
            'user_id',
        );
        $this->createIndex(
            'idx-subscription_payment-checkout_request_id',
            '{{%subscription_payment}}',
            'checkout_request_id',
        );
        $this->createIndex(
            'idx-subscription_payment-merchant_request_id',
            '{{%subscription_payment}}',
            'merchant_request_id',
        );

        $this->addForeignKey(
            'fk-subscription_payment-user_id',
            '{{%subscription_payment}}',
            'user_id',
            '{{%users}}',
            'user_id',
            'CASCADE',
            'CASCADE',
        );

        $this->execute(
            "ALTER TABLE {{%subscription_payment}} ADD CONSTRAINT subscription_payment_status_check "
            . "CHECK ((status)::text = ANY (ARRAY["
            . "('PENDING'::character varying)::text, "
            . "('COMPLETED'::character varying)::text, "
            . "('FAILED'::character varying)::text, "
            . "('CANCELLED'::character varying)::text"
            . ']))',
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk-subscription_payment-user_id', '{{%subscription_payment}}');
        $this->dropTable('{{%subscription_payment}}');
    }
}
