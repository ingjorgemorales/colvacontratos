<?php

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {

            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');
            $database = env('DB_DATABASE', 'colvacontratos');
            $username = env('DB_USERNAME', 'root');
            $password = env('DB_PASSWORD', '');

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

            try {

                self::$instance = new PDO(
                    $dsn,
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );

            } catch (PDOException $e) {

                die('Database connection error: ' . $e->getMessage());

            }
        }

        return self::$instance;
    }

    /*
    |--------------------------------------------------------------------------
    | Compatibilidad Legacy
    |--------------------------------------------------------------------------
    */
    public static function pdo(): PDO
    {
        return self::connection();
    }
}