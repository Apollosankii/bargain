<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Complaints (report auction/user) and support tickets (user ↔ admin messaging).
 */
class m260819_100000_create_complaints_and_support_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%complaints}}', [
            'complaint_id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'type' => $this->string(20)->notNull(),
            'auction_id' => $this->integer()->null(),
            'reported_user_id' => $this->integer()->null(),
            'subject' => $this->string(200)->notNull(),
            'description' => $this->text()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('OPEN'),
            'admin_notes' => $this->text()->null(),
            'reviewed_by' => $this->integer()->null(),
            'reviewed_at' => $this->timestamp()->null(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-complaints-user', '{{%complaints}}', 'user_id');
        $this->createIndex('idx-complaints-status', '{{%complaints}}', 'status');
        $this->createIndex('idx-complaints-auction', '{{%complaints}}', 'auction_id');

        $this->addForeignKey(
            'fk-complaints-user',
            '{{%complaints}}',
            'user_id',
            '{{%users}}',
            'user_id',
            'CASCADE',
            'CASCADE',
        );

        $this->addForeignKey(
            'fk-complaints-auction',
            '{{%complaints}}',
            'auction_id',
            '{{%auctions}}',
            'auction_id',
            'SET NULL',
            'CASCADE',
        );

        $this->addForeignKey(
            'fk-complaints-reported_user',
            '{{%complaints}}',
            'reported_user_id',
            '{{%users}}',
            'user_id',
            'SET NULL',
            'CASCADE',
        );

        $this->addForeignKey(
            'fk-complaints-reviewed_by',
            '{{%complaints}}',
            'reviewed_by',
            '{{%users}}',
            'user_id',
            'SET NULL',
            'CASCADE',
        );

        $this->createTable('{{%support_tickets}}', [
            'ticket_id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'subject' => $this->string(200)->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('OPEN'),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-support_tickets-user', '{{%support_tickets}}', 'user_id');
        $this->createIndex('idx-support_tickets-status', '{{%support_tickets}}', 'status');

        $this->addForeignKey(
            'fk-support_tickets-user',
            '{{%support_tickets}}',
            'user_id',
            '{{%users}}',
            'user_id',
            'CASCADE',
            'CASCADE',
        );

        $this->createTable('{{%support_messages}}', [
            'message_id' => $this->primaryKey(),
            'ticket_id' => $this->integer()->notNull(),
            'sender_id' => $this->integer()->notNull(),
            'is_admin' => $this->boolean()->notNull()->defaultValue(false),
            'body' => $this->text()->notNull(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-support_messages-ticket', '{{%support_messages}}', 'ticket_id');

        $this->addForeignKey(
            'fk-support_messages-ticket',
            '{{%support_messages}}',
            'ticket_id',
            '{{%support_tickets}}',
            'ticket_id',
            'CASCADE',
            'CASCADE',
        );

        $this->addForeignKey(
            'fk-support_messages-sender',
            '{{%support_messages}}',
            'sender_id',
            '{{%users}}',
            'user_id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%support_messages}}');
        $this->dropTable('{{%support_tickets}}');
        $this->dropTable('{{%complaints}}');
    }
}
