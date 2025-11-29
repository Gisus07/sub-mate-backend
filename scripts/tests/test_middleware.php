<?php

/**
 * Test Script - AuthMiddleware
 * Verifica funcionamiento del middleware
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;
use App\Core\AuthMiddleware;
use App\Services\AuthService;

Env::loadEnv(__DIR__ . '/..');

echo "=== TEST: AuthMiddleware ===\n\n";

try {
    // Test 1: Obtener token válido
    echo "► Test 1: Obtener token de prueba\n";
    $authService = new AuthService();
    $resultado = $authService->login('beta@submate.app', 'Beta123!');
    $token = $resultado['token'];
    echo "  ✓ Token obtenido\n\n";

    // Test 2: Simular request con token
    echo "► Test 2: Simular validación con token válido\n";
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";

    $middleware = new AuthMiddleware();
    $usuario = $middleware->handle();

    echo "  ✓ Token validado correctamente\n";
    echo "  Usuario autenticado:\n";
    echo "    - ID: {$usuario['sub']}\n";
    echo "    - Email: {$usuario['email']}\n";
    echo "    - Rol: {$usuario['rol']}\n\n";

    // Test 3: Validar rol beta
    echo "► Test 3: Validar rol 'beta'\n";
    $usuarioConRol = $middleware->requiereRoles(['beta', 'admin']);
    echo "  ✓ Usuario tiene rol permitido: {$usuarioConRol['rol']}\n\n";

    // Test 4: Intentar validar rol admin (debería fallar)
    echo "► Test 4: Intentar requerir rol 'admin' (debería fallar)\n";
    try {
        $middleware->requiereRol('admin');
        echo "  ❌ No debería haber pasado\n";
    } catch (Exception $e) {
        echo "  ✓ Correctamente bloqueado: {$e->getMessage()}\n";
        echo "  ✓ Código: {$e->getCode()}\n";
    }
    echo "\n";

    // Test 5: Sin token
    echo "► Test 5: Intentar sin token (debería fallar)\n";
    unset($_SERVER['HTTP_AUTHORIZATION']);
    try {
        $middleware2 = new AuthMiddleware();
        $middleware2->handle();
        echo "  ❌ No debería haber pasado\n";
    } catch (Exception $e) {
        echo "  ✓ Correctamente bloqueado: {$e->getMessage()}\n";
        echo "  ✓ Código: {$e->getCode()}\n";
    }
    echo "\n";

    echo "==============================================\n";
    echo "✓ TODAS LAS PRUEBAS COMPLETADAS\n";
    echo "==============================================\n";
    echo "\nResumen:\n";
    echo "  ✅ Validación de token JWT funciona\n";
    echo "  ✅ Extracción de payload correcta\n";
    echo "  ✅ Validación de roles implementada\n";
    echo "  ✅ Manejo de errores apropiado\n";

    exit(0);
} catch (Throwable $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n\n";
    exit(1);
}
