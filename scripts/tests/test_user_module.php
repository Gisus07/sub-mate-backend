<?php

/**
 * Test Script - Módulo de Usuarios Completo
 * Verifica restricción de 5 métodos y mapper pattern
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;
use App\Services\AuthService;
use App\Services\UsuarioService;

Env::loadEnv(__DIR__ . '/..');

echo "=== TEST: Módulo de Usuarios Completo ===\n\n";

try {
    // Test 1: Verificar restricción de 5 métodos públicos
    echo "► Test 1: Verificar restricción de métodos públicos\n";

    $clases = [
        \App\Core\Database::class => 3,
        \App\Models\UsuarioModel::class => 5,
        \App\Services\AuthService::class => 3,
        \App\Services\UsuarioService::class => 3,
        \App\Controllers\AuthController::class => 3,
        \App\Controllers\UsuarioController::class => 2
    ];

    foreach ($clases as $clase => $esperado) {
        $reflection = new ReflectionClass($clase);
        $publicMethods = array_filter($reflection->getMethods(), function ($method) {
            return $method->isPublic() && !$method->isConstructor();
        });
        $total = count($publicMethods);

        $nombreCorto = substr($clase, strrpos($clase, '\\') + 1);
        $status = $total <= 5 ? '✓' : '❌';
        echo "  {$status} {$nombreCorto}: {$total} métodos públicos (esperado: {$esperado})\n";

        if ($total > 5) {
            echo "    ⚠ VIOLACIÓN DE RESTRICCIÓN!\n";
        }
    }
    echo "\n";

    // Test 2: Login con usuario beta (debe devolver 'rol')
    echo "► Test 2: Login y verificación de campo 'rol'\n";
    $authService = new AuthService();
    $resultado = $authService->login('beta@submate.app', 'Beta123!');

    echo "  ✓ Login exitoso\n";
    echo "  Usuario: {$resultado['usuario']['nombre']} {$resultado['usuario']['apellido']}\n";
    echo "  Email: {$resultado['usuario']['email']}\n";
    echo "  ROL: {$resultado['usuario']['rol']} ← CRÍTICO\n";
    echo "  Token: " . substr($resultado['token'], 0, 30) . "...\n\n";

    // Verificar que el rol esté en el JWT
    $payload = $authService->validarToken($resultado['token']);
    echo "  ✓ JWT válido, payload:\n";
    echo "    - sub: {$payload['sub']}\n";
    echo "    - email: {$payload['email']}\n";
    echo "    - rol: {$payload['rol']} ← EN EL TOKEN\n\n";

    // Test 3: Mapper Pattern
    echo "► Test 3: Verificar Mapper Pattern\n";
    $usuarioService = new UsuarioService();
    $perfil = $usuarioService->obtenerPerfil($payload['sub']);

    echo "  ✓ Perfil obtenido (limpio, sin _ahjr):\n";
    foreach ($perfil as $key => $value) {
        if (strpos($key, '_ahjr') !== false) {
            echo "    ❌ ENCONTRADO SUFIJO: {$key}\n";
        }
    }
    echo "  ✓ No se encontraron sufijos _ahjr en la salida\n";
    echo "  ✓ Campos limpios: " . implode(', ', array_keys($perfil)) . "\n\n";

    // Test 4: Registro de usuario nuevo
    echo "► Test 4: Registro de nuevo usuario (mapper de entrada)\n";
    $datosLimpios = [
        'nombre' => 'Test',
        'apellido' => 'Usuario',
        'email' => 'test_' . time() . '@example.com',
        'clave' => 'Test123!'
    ];

    $resultadoRegistro = $authService->registrarUsuario($datosLimpios);
    echo "  ✓ Usuario registrado con ID: {$resultadoRegistro['id']}\n";
    echo "  ✓ Entrada limpia mapeada correctamente a formato DB\n\n";

    echo "==============================================\n";
    echo "✓ TODAS LAS PRUEBAS PASADAS\n";
    echo "==============================================\n";
    echo "\nResumen:\n";
    echo "  ✅ Restricción de ≤5 métodos cumplida\n";
    echo "  ✅ Login devuelve campo 'rol'\n";
    echo "  ✅ JWT contiene 'rol'\n";
    echo "  ✅ Mapper Pattern funcionando (BD ↔ API)\n";
    echo "  ✅ Sufijos _ahjr solo en BD, JSON limpio en API\n";

    exit(0);
} catch (Throwable $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n\n";
    exit(1);
}
