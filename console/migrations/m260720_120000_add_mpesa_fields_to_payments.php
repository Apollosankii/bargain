<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * M-Pesa STK fields for auction winner payments.
 */
class m260720_120000_add_mpesa_fields_to_payments extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%payments}}', 'phone', $this->string(20));
        $this->addColumn('{{%payments}}', 'merchant_request_id', $this->string(100));
        $this->addColumn('{{%payments}}', 'checkout_request_id', $this->string(100));
        $this->addColumn('{{%payments}}', 'mpesa_receipt', $this->string(50));
        $this->addColumn('{{%payments}}', 'result_code', $this->integer());
        $this->addColumn('{{%payments}}', 'result_desc', $this->text());

        $this->createIndex(
            'idx-payments-checkout_request_id',
            '{{%payments}}',
            'checkout_request_id',
        );
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx-payments-checkout_request_id', '{{%payments}}');
        $this->dropColumn('{{%payments}}', 'result_desc');
        $this->dropColumn('{{%payments}}', 'result_code');
        $this->dropColumn('{{%payments}}', 'mpesa_receipt');
        $this->dropColumn('{{%payments}}', 'checkout_request_id');
        $this->dropColumn('{{%payments}}', 'merchant_request_id');
        $this->dropColumn('{{%payments}}', 'phone');
    }
}
