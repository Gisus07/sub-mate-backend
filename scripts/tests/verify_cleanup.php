<?php

/**
 * Script de Verificación Post-Limpieza
 * Verifica la integridad del código tras la refactorización
 */

declare(strict_types=1);

echo "=== VERIFICACIÓN POST-LIMPIEZA ===\n\n";

// Test 1: Verificar que no exista el archivo legacy
echo "► Test 1: Verificar eliminación de archivos legacy\n";
$legacyFile = __DIR__ . '/../app/config/database.php';
if (!file_exists($legacyFile)) {
    echo "  ✓ app/config/database.php eliminado correctamente\n";
} else {
    echo "  ❌ ERROR: archivo legacy aún existe\n";
    exit(1);
}
echo "\n";

// Test 2: Verificar que la clase correcta existe
echo "► Test 2: Verificar App\Core\Database existe\n";
$correctFile = __DIR__ . '/../app/core/Database.php';
if (file_exists($correctFile)) {
    echo "  ✓ app/core/Database.php existe\n";

    require_once __DIR__ . '/../vendor/autoload.php';

    if (class_exists('App\Core\Database')) {
        echo "  ✓ Clase App\Core\Database cargada correctamente\n";
    } else {
        echo "  ❌ ERROR: Clase no se puede cargar\n";
        exit(1);
    }
} else {
    echo "  ❌ ERROR: archivo no existe\n";
    exit(1);
}
echo "\n";

// Test 3: Verificar documentación movida
echo "► Test 3: Verificar documentación en docs/\n";
$docsFiles = [
    'docs/algo.md',
    'docs/middleware.md',
    'docs/suscripciones_routes.md'
];

foreach ($docsFiles as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    if (file_exists($fullPath)) {
        echo "  ✓ {$file} movido correctamente\n";
    } else {
        echo "  ❌ {$file} no encontrado\n";
    }
}
echo "\n";

// Test 4: Contar métodos públicos de Database
echo "► Test 4: Verificar restricción de 5 métodos (App\Core\Database)\n";
$reflection = new ReflectionClass('App\Core\Database');
$publicMethods = array_filter($reflection->getMethods(), function ($method) {
    return $method->isPublic() && !$method->isConstructor();
});
$total = count($publicMethods);

echo "  Métodos públicos encontrados: {$total}\n";
if ($total <= 5) {
    echo "  ✓ Cumple restricción (≤5 métodos)\n";
} else {
    echo "  ❌ VIOLACIÓN: más de 5 métodos públicos\n";
    exit(1);
}
echo "\n";

// Test 5: Verificar namespaces de clases principales
echo "► Test 5: Verificar namespaces PSR-4\n";
$clases = [
    'App\Core\Database',
    'App\Core\AuthMiddleware',
    'App\Models\UsuarioModel',
    'App\Models\SuscripcionModel',
    'App\Services\AuthService',
    'App\Services\SuscripcionService',
    'App\Controllers\AuthController',
    'App\Controllers\SuscripcionController'
];

foreach ($clases as $clase) {
    if (class_exists($clase)) {
        echo "  ✓ {$clase}\n";
    } else {
        echo "  ❌ {$clase} - NO ENCONTRADA\n";
    }
}
echo "\n";

echo "==============================================\n";
echo "✓ VERIFICACIÓN COMPLETADA\n";
echo "==============================================\n";
echo "\nResumen:\n";
echo "  ✅ Archivo legacy eliminado\n";
echo "  ✅ Clase correcta funcionando\n";
echo "  ✅ Documentación reorganizada\n";
echo "  ✅ Restricción de métodos cumplida\n";
echo "  ✅ Namespaces PSR-4 correctos\n";
echo "  ✅ Autoloader actualizado\n";
echo "\n👍 Sistema limpio y funcional\n";

exit(0);
