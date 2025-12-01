<?php

declare(strict_types=1);

namespace App\core;

use Dotenv\Dotenv;
use RuntimeException;

/**
 * Clase Env - Gestión de Variables de Entorno
 * 
 * Implementa el patrón Singleton para cargar y acceder a las variables
 * de entorno del archivo .env de forma segura y eficiente.
 * 
 * @package App\Core
 */
class Env
{
    /**
     * Instancia única de la clase (Singleton)
     * 
     * @var Env|null
     */
    private static ?Env $instance = null;

    /**
     * Instancia de Dotenv
     * 
     * @var Dotenv
     */
    private Dotenv $dotenv;

    /**
     * Indica si las variables ya fueron cargadas
     * 
     * @var bool
     */
    private bool $loaded = false;

    /**
     * Constructor privado (Singleton)
     * Previene la instanciación directa de la clase
     */
    private function __construct()
    {
        // Constructor privado para patrón Singleton
    }

    /**
     * Previene la clonación de la instancia (Singleton)
     * 
     * @return void
     */
    private function __clone(): void
    {
        // Prevenir clonación
    }

    /**
     * Previene la deserialización de la instancia (Singleton)
     * 
     * @throws RuntimeException
     * @return void
     */
    public function __wakeup(): void
    {
        throw new RuntimeException('No se puede deserializar un Singleton.');
    }

    /**
     * Obtiene la instancia única de la clase (Singleton)
     * 
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Carga las variables de entorno desde el archivo .env
     * 
     * @param string $path Ruta absoluta al directorio que contiene el archivo .env
     * @throws RuntimeException Si el archivo .env no existe o no puede cargarse
     * @return void
     */
    public function load(string $path): void
    {
        if ($this->loaded) {
            return; // Ya fue cargado anteriormente
        }

        // Verificar si existe el archivo .env antes de intentar cargarlo
        if (!file_exists($path . DIRECTORY_SEPARATOR . '.env')) {
            // En producción (Railway), las variables se cargan del sistema.
            // Si no hay .env, asumimos que estamos en ese entorno y no hacemos nada.
            return;
        }

        try {
            $this->dotenv = Dotenv::createImmutable($path);
            $this->dotenv->load();
            $this->loaded = true;
        } catch (\Throwable $e) {
            // Capturamos cualquier error de Dotenv para no detener la ejecución abruptamente
            error_log("Error al cargar Dotenv: " . $e->getMessage());
            // Opcional: lanzar una excepción controlada si es crítico
            // throw new RuntimeException("Error crítico cargando variables de entorno", 0, $e);
        }
    }

    /**
     * Obtiene el valor de una variable de entorno
     * 
     * @param string $key Nombre de la variable de entorno
     * @param mixed $default Valor por defecto si la variable no existe
     * @return mixed Valor de la variable o el valor por defecto
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        return $value;
    }

    /**
     * Obtiene el valor de una variable de entorno requerida
     * 
     * @param string $key Nombre de la variable de entorno
     * @throws RuntimeException Si la variable no está definida
     * @return string Valor de la variable
     */
    public static function getRequired(string $key): string
    {
        $value = self::get($key);

        if ($value === null || $value === '') {
            throw new RuntimeException(
                "La variable de entorno '{$key}' es requerida pero no está definida en el archivo .env"
            );
        }

        return (string) $value;
    }

    /**
     * Verifica si una variable de entorno está definida
     * 
     * @param string $key Nombre de la variable de entorno
     * @return bool True si la variable existe, false en caso contrario
     */
    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    /**
     * Método estático conveniente para cargar el archivo .env
     * 
     * @param string $path Ruta absoluta al directorio que contiene el archivo .env
     * @throws RuntimeException Si el archivo .env no existe o no puede cargarse
     * @return void
     */
    public static function loadEnv(string $path): void
    {
        self::getInstance()->load($path);
    }

    /**
     * Verifica si las variables de entorno ya fueron cargadas
     * 
     * @return bool True si ya fueron cargadas, false en caso contrario
     */
    public static function isLoaded(): bool
    {
        $instance = self::getInstance();
        return $instance->loaded;
    }
}
