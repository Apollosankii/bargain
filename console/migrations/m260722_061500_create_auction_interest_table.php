<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Bidders can mark interest in upcoming auctions and get notified when bidding opens.
 */
class m260722_061500_create_auction_interest_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%auction_interest}}', [
            'interest_id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'auction_id' => $this->integer()->notNull(),
            'notified_at' => $this->timestamp()->null(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex(
            'idx-auction_interest-user_auction',
            '{{%auction_interest}}',
            ['user_id', 'auction_id'],
            true,
        );

        $this->addForeignKey(
            'fk-auction_interest-user',
            '{{%auction_interest}}',
            'user_id',
            '{{%users}}',
            'user_id',
            'CASCADE',
            'CASCADE',
        );

        $this->addForeignKey(
            'fk-auction_interest-auction',
            '{{%auction_interest}}',
            'auction_id',
            '{{%auctions}}',
            'auction_id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%auction_interest}}');
    }
}
