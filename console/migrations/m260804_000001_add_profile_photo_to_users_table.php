<?php

declare(strict_types=1);

use yii\db\Migration;

class m260804_000001_add_profile_photo_to_users_table extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%users}}', 'profile_photo', $this->string(500)->null()->after('status'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%users}}', 'profile_photo');
    }
}
