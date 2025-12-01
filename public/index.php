<?php

// =============================================================================
// MANEJO DE ARCHIVOS ESTÁTICOS (Servidor interno de PHP)
// =============================================================================

// Manejo de archivos estáticos para el servidor interno de PHP
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Si el archivo existe físicamente y no es la raíz, sírvelo directamente
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

/**
 * SubMate Backend - Entry Point
 * 
 * Sistema modular con enrutamiento centralizado y soporte CORS.
 */

// =============================================================================
// CORE DEPENDENCIES & AUTOLOAD
// =============================================================================

// Cargar autoloader de Composer PRIMERO
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Fallback crítico si no hay autoloader
    die('Error: No se encontró vendor/autoload.php. Ejecuta "composer install".');
}

use App\core\Env;
use App\core\Database;
use App\core\Router;

// =============================================================================
// CORS CONFIGURATION
// =============================================================================

// Lista de orígenes permitidos (whitelist)
$allowed_origins = [
    'http://localhost:3000',      // React Dev Server (Create React App)
    'http://localhost:5173',      // Vite Dev Server
    'http://localhost:4173',      // Vite Preview
    'https://submate.app',        // Producción
    'https://www.submate.app',    // Producción con www
];

// Obtener el origen de la petición
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Validar si el origen está permitido
$origin_allowed = false;

if (in_array($origin, $allowed_origins)) {
    // El origen está en la lista blanca
    $origin_allowed = true;
} elseif (strpos($origin, 'http://localhost:') === 0 || strpos($origin, 'http://127.0.0.1:') === 0) {
    // Permitir cualquier puerto localhost en desarrollo
    $origin_allowed = true;
}

// Si el origen está permitido, devolver el header dinámicamente
if ($origin_allowed && !empty($origin)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}

// Headers CORS comunes (siempre se envían)
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // 24 horas de cache para preflight

// Manejar peticiones OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// =============================================================================
// INITIALIZATION
// =============================================================================

// Cargar variables de entorno
Env::loadEnv(__DIR__ . '/..');

// Inicializar base de datos (Singleton)
$database_ahjr = Database::getInstance();
$db_ahjr = $database_ahjr->getConnection();

// Inicializar Router
$router_ahjr = new Router();

// =============================================================================
// LOAD ROUTES
// =============================================================================

// Cargar todas las rutas desde el archivo centralizado
require_once __DIR__ . '/../app/routes/api.php';

// =============================================================================
// DISPATCH REQUEST
// =============================================================================

// Ejecutar el router
$router_ahjr->run_ahjr();
