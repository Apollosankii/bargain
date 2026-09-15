<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Multiple photos per auction (min 3 required at create time in the form).
 */
class m260722_063000_create_auction_images_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%auction_images}}', [
            'image_id' => $this->primaryKey(),
            'auction_id' => $this->integer()->notNull(),
            'image_url' => $this->string(500)->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-auction_images-auction_id', '{{%auction_images}}', 'auction_id');
        $this->addForeignKey(
            'fk-auction_images-auction',
            '{{%auction_images}}',
            'auction_id',
            '{{%auctions}}',
            'auction_id',
            'CASCADE',
            'CASCADE',
        );

        // Backfill cover image_url from existing auctions into gallery table.
        $this->execute(
            "INSERT INTO {{%auction_images}} (auction_id, image_url, sort_order)
             SELECT auction_id, image_url, 0
             FROM {{%auctions}}
             WHERE image_url IS NOT NULL AND image_url <> ''",
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%auction_images}}');
    }
}
