<?php

declare(strict_types=1);

use common\config\DbEnv;

$db = DbEnv::connectionConfig() ?? [
    'class' => \yii\db\Connection::class,
    'dsn' => 'pgsql:host=localhost;port=5432;dbname=bargain_yii',
    'username' => 'postgres',
    'password' => '',
    'charset' => 'utf8',
];

return [
    'name' => 'Bargain',
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                'viewPath' => '@common/mail',
                'useFileTransport' => true,
            ],
        ],
    ],
    'components' => [
        'db' => $db,
        'mailer' => \yii\mail\MailerInterface::class,
    ],
];
