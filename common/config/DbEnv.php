<?php

declare(strict_types=1);

namespace common\config;

/**
 * Build Yii DB config from DATABASE_URL (Render, Railway, etc.).
 */
final class DbEnv
{
    /**
     * @return array{class: class-string, dsn: string, username: string, password: string, charset: string}|null
     */
    public static function connectionConfig(): ?array
    {
        $databaseUrl = getenv('DATABASE_URL') ?: getenv('RENDER_DATABASE_URL') ?: '';
        if ($databaseUrl === '') {
            return null;
        }

        $parts = parse_url($databaseUrl);
        if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
            return null;
        }

        $host = $parts['host'];
        $port = $parts['port'] ?? 5432;
        $dbname = ltrim($parts['path'], '/');
        $user = rawurldecode($parts['user'] ?? '');
        $pass = rawurldecode($parts['pass'] ?? '');

        return [
            'class' => \yii\db\Connection::class,
            'dsn' => sprintf('pgsql:host=%s;port=%d;dbname=%s;sslmode=require', $host, $port, $dbname),
            'username' => $user,
            'password' => $pass,
            'charset' => 'utf8',
        ];
    }
}
