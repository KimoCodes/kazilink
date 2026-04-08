<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;
    private static array $tableExistsCache = [];

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = require BASE_PATH . '/app/config/app.php';
        $db = $config['db'];

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $db['host'],
            $db['port'],
            $db['name']
        );

        self::$connection = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$connection;
    }

    public static function tableExists(string $table): bool
    {
        $table = trim($table);

        if ($table === '') {
            return false;
        }

        if (array_key_exists($table, self::$tableExistsCache)) {
            return self::$tableExistsCache[$table];
        }

        $statement = self::connection()->prepare('
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = :table_name
            LIMIT 1
        ');
        $statement->execute(['table_name' => $table]);
        self::$tableExistsCache[$table] = $statement->fetchColumn() !== false;

        return self::$tableExistsCache[$table];
    }
}
