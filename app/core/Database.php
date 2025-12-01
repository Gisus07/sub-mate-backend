<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Database - Singleton para conexión PDO
 * 
 * RESTRICCIÓN: Máximo 5 métodos públicos
 */
class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;

    private function __construct()
    {
        // Constructor privado (Singleton)
    }

    private function __clone(): void
    {
        // Prevenir clonación
    }

    /**
     * 1. Obtiene la instancia única (Singleton)
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 2. Obtiene la conexión PDO
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * 3. Método estático conveniente para obtener PDO directamente
     */
    public static function getDB(): PDO
    {
        return self::getInstance()->getConnection();
    }

    private function connect(): void
    {
        try {
            $host = Env::get('DB_HOST');
            $db = Env::get('DB_NAME');
            $user = Env::get('DB_USER');
            $pass = Env::get('DB_PASS');
            $port = Env::get('DB_PORT');

            if (!$host) {
                throw new RuntimeException("Error Crítico: Variables de entorno de base de datos no configuradas.");
            }

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

            $this->connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Error de conexión: " . $e->getMessage());
        }
    }
}
