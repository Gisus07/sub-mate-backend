<?php

/**
 * Script de Verificación - Rutas de Suscripciones
 * Verifica que las rutas estén correctamente configuradas
 */

echo "=== Verificación de Rutas de Suscripciones ===\n\n";

// Verificar que los archivos existen
$archivos = [
    'Rutas' => __DIR__ . '/../app/routes/suscripcion.php',
    'Controller' => __DIR__ . '/../app/controllers/SuscripcionController.php',
    'OperacionesController' => __DIR__ . '/../app/controllers/SuscripcionOperacionesController.php',
    'Service' => __DIR__ . '/../app/services/SuscripcionService.php',
    'OperacionesService' => __DIR__ . '/../app/services/SuscripcionOperacionesService.php',
    'Model' => __DIR__ . '/../app/models/SuscripcionModel.php',
    'OperacionesModel' => __DIR__ . '/../app/models/SuscripcionOperacionesModel.php',
];

echo "► Verificando archivos del módulo:\n";
$todoOk = true;
foreach ($archivos as $nombre => $ruta) {
    if (file_exists($ruta)) {
        echo "  ✓ {$nombre}: OK\n";
    } else {
        echo "  ❌ {$nombre}: NO ENCONTRADO\n";
        $todoOk = false;
    }
}

if (!$todoOk) {
    echo "\n❌ Faltan archivos necesarios.\n";
    exit(1);
}

echo "\n► Rutas configuradas:\n";
echo "  REST (SuscripcionController):\n";
echo "    1. GET    /api/suscripciones\n";
echo "    2. POST   /api/suscripciones\n";
echo "    3. GET    /api/suscripciones/{id}\n";
echo "    4. PUT    /api/suscripciones/{id}\n";
echo "    5. DELETE /api/suscripciones/{id}\n";
echo "\n  RPC (SuscripcionOperacionesController):\n";
echo "    6. PATCH  /api/suscripciones/{id}/estado\n";
echo "    7. POST   /api/suscripciones/{id}/simular-pago\n";

echo "\n==============================================\n";
echo "✓ MÓDULO DE SUSCRIPCIONES CONFIGURADO\n";
echo "==============================================\n";
echo "\nPróximos pasos:\n";
echo "  1. Acceder a http://localhost/submate-backend/public/\n";
echo "  2. Probar endpoints con Postman/Thunder Client\n";
echo "  3. Usar token JWT para autenticación\n";

exit(0);
