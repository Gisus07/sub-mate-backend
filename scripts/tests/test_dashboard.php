<?php

/**
 * Test del Dashboard Module
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/core/Env.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/DashboardModel.php';
require_once __DIR__ . '/../app/services/DashboardService.php';

use App\core\Env;
use App\Services\DashboardService;
use App\Models\DashboardModel;

try {
    echo "=== TEST: Dashboard Module ===\n\n";

    // Cargar ENV
    Env::loadEnv(__DIR__ . '/..');

    echo "=== TEST MODEL ===\n";
    $model = new DashboardModel();

    echo "1. Contar activas: ";
    $activas = $model->contarActivas($uid);
    echo "{$activas} suscripciones\n";

    echo "2. Gasto mes actual: ";
    $mesActual = (int) date('n');
    $anioActual = (int) date('Y');
    $gasto = $model->obtenerGastoTotalMes($uid, $mesActual, $anioActual);
    echo "$" . number_format($gasto, 2) . "\n";

    echo "3. Gasto por categoría: ";
    $categorias = $model->obtenerGastoPorCategoria($uid);
    echo json_encode($categorias) . "\n";

    echo "4. Historial 6 meses: ";
    $historial = $model->obtenerHistorialUltimos6Meses($uid);
    echo count($historial) . " meses con datos\n";

    echo "5. Próximo vencimiento: ";
    $proximo = $model->obtenerProximoVencimiento($uid);
    echo $proximo ? $proximo['nombre_servicio_ahjr'] : "N/A";
    echo "\n\n";

    // Test DashboardService
    echo "=== TEST SERVICE (Chart.js Format) ===\n";
    $service = new DashboardService();

    echo "1. Resumen:\n";
    $resumen = $service->generarResumen($uid);
    echo json_encode($resumen, JSON_PRETTY_PRINT) . "\n\n";

    echo "2. Gráfica Mensual:\n";
    $graficaMensual = $service->prepararDatosGraficaMensual($uid);
    echo "   Labels: " . implode(', ', $graficaMensual['labels']) . "\n";
    echo "   Data: " . implode(', ', $graficaMensual['data']) . "\n\n";

    echo "3. Distribución Métodos:\n";
    $metodos = $service->prepararDistribucionMetodos($uid);
    echo "   Labels: " . implode(', ', $metodos['labels']) . "\n";
    echo "   Data: " . implode(', ', $metodos['data']) . "\n\n";

    // Test completo consolidado
    echo "=== PAYLOAD COMPLETO (Como lo recibirá el Frontend) ===\n";
    $payload = [
        'resumen' => $service->generarResumen($uid),
        'grafica_mensual' => $service->prepararDatosGraficaMensual($uid),
        'distribucion_metodos' => $service->prepararDistribucionMetodos($uid)
    ];

    echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

    echo "✅ TODOS LOS TESTS PASARON\n";
} catch (Throwable $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n\n";
    exit(1);
}
