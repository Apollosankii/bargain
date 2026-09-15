<?php

declare(strict_types=1);

namespace console\controllers;

use common\models\Auction;
use common\models\Category;
use common\models\Subscription;
use common\models\User;
use common\services\RbacService;
use DateInterval;
use DateTimeImmutable;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class InstallController extends Controller
{
    /**
     * Prepare database schema, RBAC, and demo data for hosted preview.
     */
    public function actionDemoDb(): int
    {
        $db = Yii::$app->db;

        if ($db->driverName !== 'pgsql') {
            $this->stderr("Demo install expects PostgreSQL.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $schemaFile = Yii::getAlias('@app/../deploy/schema-postgresql.sql');
        if (!is_file($schemaFile)) {
            $this->stderr("Missing schema file: {$schemaFile}\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        try {
            $hasUsers = $db->schema->getTableSchema('{{%users}}', true) !== null
                && (int) $db->createCommand('SELECT COUNT(*) FROM {{%users}}')->queryScalar() > 0;
        } catch (\Throwable $e) {
            $this->stdout("Database not ready yet: {$e->getMessage()}\n", Console::FG_YELLOW);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$hasUsers) {
            $this->stdout("Applying PostgreSQL schema…\n");
            $sql = file_get_contents($schemaFile);
            if ($sql === false) {
                return ExitCode::UNSPECIFIED_ERROR;
            }
            foreach (self::splitSqlStatements($sql) as $statement) {
                $db->createCommand($statement)->execute();
            }
        } else {
            $this->stdout("Database already has users — skipping schema bootstrap.\n");
        }

        $this->stdout("Running RBAC migrations…\n");
        Yii::$app->runAction('migrate', [
            'migrationPath' => '@yii/rbac/migrations',
            'interactive' => false,
        ]);

        RbacService::initRolesAndPermissions();

        $this->stdout("Seeding demo accounts…\n");
        $this->seedDemoUsers();
        $this->seedDemoCatalog();

        RbacService::syncAllUsers();

        $this->stdout("Demo database ready.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }

    private function seedDemoUsers(): void
    {
        $password = getenv('DEMO_USER_PASSWORD') ?: 'BargainDemo2026!';
        $accounts = [
            ['Demo', 'Admin', 'demo-admin@bargain.app', '+254700000001', User::ROLE_ADMIN],
            ['Demo', 'Auctioneer', 'demo-auctioneer@bargain.app', '+254700000002', User::ROLE_AUCTIONEER],
            ['Demo', 'Bidder', 'demo-bidder@bargain.app', '+254700000003', User::ROLE_BIDDER],
        ];

        foreach ($accounts as [$first, $last, $email, $phone, $role]) {
            $user = User::findOne(['email' => $email]) ?? new User();
            $user->first_name = $first;
            $user->last_name = $last;
            $user->email = $email;
            $user->phone = $phone;
            $user->role = $role;
            $user->status = User::STATUS_ACTIVE;
            $user->setPassword($password);
            if ($user->isNewRecord) {
                $user->generateAuthKey();
            }
            $user->save(false);

            if ($role === User::ROLE_AUCTIONEER) {
                $this->ensureActiveSubscription((int) $user->user_id);
            }
        }
    }

    private function ensureActiveSubscription(int $userId): void
    {
        $existing = Subscription::find()
            ->where(['user_id' => $userId, 'status' => Subscription::STATUS_ACTIVE])
            ->andWhere(['>=', 'end_date', date('Y-m-d')])
            ->exists();

        if ($existing) {
            return;
        }

        $start = new DateTimeImmutable('today');
        $end = $start->add(new DateInterval('P365D'));

        $sub = new Subscription();
        $sub->user_id = $userId;
        $sub->plan = Subscription::PLAN_12_MONTHS;
        $sub->start_date = $start->format('Y-m-d');
        $sub->end_date = $end->format('Y-m-d');
        $sub->status = Subscription::STATUS_ACTIVE;
        $sub->save(false);
    }

    /**
     * @return list<string>
     */
    private static function splitSqlStatements(string $sql): array
    {
        $lines = preg_split('/\R/', $sql) ?: [];
        $lines = array_filter(
            $lines,
            static fn (string $line): bool => !preg_match('/^\s*--/', $line),
        );
        $sql = trim(implode("\n", $lines));

        $statements = [];
        foreach (preg_split('/;\s*\n/', $sql) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk !== '') {
                $statements[] = $chunk;
            }
        }

        return $statements;
    }

    private function seedDemoCatalog(): void
    {
        if (Category::find()->exists()) {
            return;
        }

        $categories = [
            ['Electronics', 'Phones, laptops, and gadgets'],
            ['Collectibles', 'Rare items and memorabilia'],
            ['Home & Garden', 'Furniture and appliances'],
        ];

        foreach ($categories as [$name, $description]) {
            $cat = new Category();
            $cat->name = $name;
            $cat->description = $description;
            $cat->save(false);
        }

        $auctioneer = User::findOne(['email' => 'demo-auctioneer@bargain.app']);
        $category = Category::find()->one();
        if ($auctioneer === null || $category === null || Auction::find()->exists()) {
            return;
        }

        $start = new DateTimeImmutable('now');
        $end = $start->add(new DateInterval('P7D'));

        $auction = new Auction();
        $auction->auctioneer_id = (int) $auctioneer->user_id;
        $auction->category_id = (int) $category->category_id;
        $auction->title = 'Vintage Camera Kit';
        $auction->description = 'Demo listing for the hosted Bargain preview. Place bids as the demo bidder account.';
        $auction->starting_bid = '5000';
        $auction->current_bid = '5000';
        $auction->start_time = $start->format('Y-m-d H:i:s');
        $auction->end_time = $end->format('Y-m-d H:i:s');
        $auction->status = Auction::STATUS_ACTIVE;
        $auction->save(false);
    }
}
