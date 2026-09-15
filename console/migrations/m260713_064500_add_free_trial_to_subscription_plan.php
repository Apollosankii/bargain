<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Allow FREE_TRIAL on the existing Bargain `subscription.plan` CHECK.
 */
class m260713_064500_add_free_trial_to_subscription_plan extends Migration
{
    public function safeUp(): void
    {
        $this->execute('ALTER TABLE {{%subscription}} DROP CONSTRAINT IF EXISTS subscription_plan_check');
        $this->execute(
            "ALTER TABLE {{%subscription}} ADD CONSTRAINT subscription_plan_check "
            . "CHECK ((plan)::text = ANY (ARRAY["
            . "('1_MONTH'::character varying)::text, "
            . "('6_MONTHS'::character varying)::text, "
            . "('12_MONTHS'::character varying)::text, "
            . "('FREE_TRIAL'::character varying)::text"
            . ']))',
        );
    }

    public function safeDown(): void
    {
        $this->execute(
            "UPDATE {{%subscription}} SET status = 'CANCELLED' WHERE plan = 'FREE_TRIAL' AND status = 'ACTIVE'",
        );
        $this->execute("DELETE FROM {{%subscription}} WHERE plan = 'FREE_TRIAL'");
        $this->execute('ALTER TABLE {{%subscription}} DROP CONSTRAINT IF EXISTS subscription_plan_check');
        $this->execute(
            "ALTER TABLE {{%subscription}} ADD CONSTRAINT subscription_plan_check "
            . "CHECK ((plan)::text = ANY (ARRAY["
            . "('1_MONTH'::character varying)::text, "
            . "('6_MONTHS'::character varying)::text, "
            . "('12_MONTHS'::character varying)::text"
            . ']))',
        );
    }
}
