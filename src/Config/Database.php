<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $env = self::loadEnv();
            
            $host = $env['DB_HOST'] ?? 'localhost';
            $dbname = $env['DB_NAME'] ?? 'millet_marketplace';
            $username = $env['DB_USER'] ?? 'root';
            $password = $env['DB_PASS'] ?? '';

            try {
                $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
                self::$connection = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new \Exception("Database connection failed");
            }
        }

        return self::$connection;
    }

    private static function loadEnv(): array
    {
        $envFile = __DIR__ . '/../../.env';
        $env = [];

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                list($key, $value) = explode('=', $line, 2);
                $env[trim($key)] = trim($value);
            }
        }

        return $env;
    }

    public static function getEnv(string $key, $default = null)
    {
        static $env = null;
        if ($env === null) {
            $env = self::loadEnv();
        }
        return $env[$key] ?? $default;
    }
}
