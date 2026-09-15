<?php

declare(strict_types=1);

$db = [
    'class' => \yii\db\Connection::class,
    'dsn' => 'pgsql:host=localhost;port=5432;dbname=bargain_yii',
    'username' => 'postgres',
    'password' => '',
    'charset' => 'utf8',
];

$databaseUrl = getenv('DATABASE_URL') ?: getenv('RENDER_DATABASE_URL') ?: '';
if ($databaseUrl !== '') {
    $parts = parse_url($databaseUrl);
    if ($parts !== false && !empty($parts['host']) && !empty($parts['path'])) {
        $host = $parts['host'];
        $port = $parts['port'] ?? 5432;
        $dbname = ltrim($parts['path'], '/');
        $user = rawurldecode($parts['user'] ?? '');
        $pass = rawurldecode($parts['pass'] ?? '');

        $db = [
            'class' => \yii\db\Connection::class,
            'dsn' => sprintf('pgsql:host=%s;port=%d;dbname=%s;sslmode=require', $host, $port, $dbname),
            'username' => $user,
            'password' => $pass,
            'charset' => 'utf8',
        ];
    }
}

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
