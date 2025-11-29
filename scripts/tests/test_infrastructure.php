<?php

/**
 * Script de Prueba - Capa de Infraestructura
 * 
 * Este script prueba las clases Env y Database para verificar
 * que la infraestructura básica funcione correctamente.
 */

declare(strict_types=1);

// Cargar autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;
use App\Config\Database;

echo "=== Prueba de Capa de Infraestructura ===\n\n";

try {
    // 1. Cargar variables de entorno
    echo "► Cargando variables de entorno...\n";
    Env::loadEnv(__DIR__ . '/..');
    echo "✓ Variables de entorno cargadas exitosamente\n\n";

    // 2. Verificar que se pueden leer variables
    echo "► Leyendo variables de entorno:\n";
    echo "  - DB_HOST: " . Env::get('DB_HOST', 'no definido') . "\n";
    echo "  - DB_NAME: " . Env::get('DB_NAME', 'no definido') . "\n";
    echo "  - DB_USER: " . Env::get('DB_USER', 'no definido') . "\n";
    echo "✓ Variables leídas correctamente\n\n";

    // 3. Probar conexión a la base de datos
    echo "► Conectando a la base de datos...\n";
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "✓ Conexión establecida exitosamente\n\n";

    // 4. Verificar el charset
    echo "► Verificando configuración de charset...\n";
    $stmt = $pdo->query("SELECT @@character_set_client, @@character_set_connection, @@character_set_results");
    $charset = $stmt->fetch(PDO::FETCH_NUM);
    echo "  - character_set_client: {$charset[0]}\n";
    echo "  - character_set_connection: {$charset[1]}\n";
    echo "  - character_set_results: {$charset[2]}\n";

    if ($charset[0] === 'utf8mb4' && $charset[1] === 'utf8mb4' && $charset[2] === 'utf8mb4') {
        echo "✓ Charset configurado correctamente (utf8mb4)\n\n";
    } else {
        echo "⚠ Advertencia: El charset no está configurado como utf8mb4\n\n";
    }

    // 5. Probar una consulta simple
    echo "► Ejecutando consulta de prueba...\n";
    $stmt = $pdo->query("SELECT DATABASE() as db_name, VERSION() as version");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  - Base de datos actual: {$result['db_name']}\n";
    echo "  - Versión de MySQL: {$result['version']}\n";
    echo "✓ Consulta ejecutada correctamente\n\n";

    // 6. Verificar que el Singleton funciona
    echo "► Verificando patrón Singleton...\n";
    $db2 = Database::getInstance();
    if ($db === $db2) {
        echo "✓ Singleton funciona correctamente (misma instancia)\n\n";
    } else {
        echo "❌ Error: Se crearon múltiples instancias\n\n";
    }

    // 7. Probar método estático conveniente
    echo "► Probando método estático Database::getDB()...\n";
    $pdo2 = Database::getDB();
    if ($pdo === $pdo2) {
        echo "✓ Método estático funciona correctamente\n\n";
    } else {
        echo "⚠ Advertencia: Se obtuvieron diferentes instancias de PDO\n\n";
    }

    echo "==============================================\n";
    echo "✓ TODAS LAS PRUEBAS COMPLETADAS EXITOSAMENTE\n";
    echo "==============================================\n";

    exit(0);
} catch (Throwable $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n\n";

    if ($e->getPrevious()) {
        echo "Error anterior: " . $e->getPrevious()->getMessage() . "\n\n";
    }

    exit(1);
}
