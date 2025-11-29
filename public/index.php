<?php

/**
 * SubMate Backend - Entry Point
 * 
 * Sistema modular con enrutamiento centralizado y soporte CORS.
 */

// =============================================================================
// CORS CONFIGURATION
// =============================================================================

// Permitir solicitudes desde el frontend React
header('Access-Control-Allow-Origin: http://localhost:3000'); // Cambiar a dominio en producción
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400'); // 24 horas de cache para preflight

// Manejar peticiones OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// =============================================================================
// CORE DEPENDENCIES
// =============================================================================

require_once __DIR__ . '/../app/core/Response.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Env.php';
require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Env;
use App\Core\Database;

// Cargar variables de entorno
Env::loadEnv(__DIR__ . '/..');

// Cargar autoloader de Composer si existe
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

require_once __DIR__ . '/../app/core/Mailer.php';

// =============================================================================
// INITIALIZATION
// =============================================================================

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
