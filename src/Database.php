<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '5432';
            $db = getenv('DB_NAME') ?: 'access_cards';
            $user = getenv('DB_USER') ?: 'access_user';
            $password = getenv('DB_PASSWORD') ?: 'access_pass';

            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $db);
            self::$instance = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        return self::$instance;
    }
}
