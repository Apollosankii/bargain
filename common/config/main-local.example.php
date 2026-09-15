<?php

declare(strict_types=1);

/**
 * Copy to main-local.php and fill in your database credentials.
 * main-local.php is gitignored and never committed.
 */
return [
    'name' => 'Bargain',
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'pgsql:host=localhost;port=5432;dbname=bargain_yii',
            'username' => 'postgres',
            'password' => 'YOUR_DB_PASSWORD',
            'charset' => 'utf8',
        ],
    ],
];
