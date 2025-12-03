<?php

declare(strict_types=1);

namespace App\services;

use App\models\DashboardModel;
use DateTime;

/**
 * DashboardService - Formateo para UI (Chart.js)
 * 
 * RESTRICCIÓN: Máximo 5 métodos públicos
 * Responsabilidad: Transformar datos del modelo a formato Chart.js
 */
class DashboardService
{
    private DashboardModel $model_AHJR;

    public function __construct()
    {
        $this->model_AHJR = new DashboardModel();
    }

    /**
     * 1. Genera resumen con KPIs principales
     */
    public function generarResumen_AHJR(int $uid_AHJR): array
    {
        $mesActual_AHJR = (int) date('n');
        $anioActual_AHJR = (int) date('Y');

        $totalActivas_AHJR = $this->model_AHJR->contarActivas_AHJR($uid_AHJR);
        $gastoMesActual_AHJR = $this->model_AHJR->obtenerGastoTotalMes_AHJR($uid_AHJR, $mesActual_AHJR, $anioActual_AHJR);
        $gastoEstimado_AHJR = $this->model_AHJR->obtenerGastoMensualEstimado_AHJR($uid_AHJR);
        $proximoVencimiento_AHJR = $this->model_AHJR->obtenerProximoVencimiento_AHJR($uid_AHJR);

        // Nuevos KPIs
        $proyeccionAnual_AHJR = $gastoEstimado_AHJR * 12;
        $mayorGastoArr_AHJR = $this->model_AHJR->obtenerTopSuscripciones_AHJR($uid_AHJR, 1);
        $mayorGasto_AHJR = !empty($mayorGastoArr_AHJR) ? $mayorGastoArr_AHJR[0] : null;

        $resumen_AHJR = [
            'total_activas' => $totalActivas_AHJR,
            'gasto_mes_actual' => round($gastoMesActual_AHJR, 2),
            'gasto_mensual_estimado' => round($gastoEstimado_AHJR, 2),
            'proyeccion_anual' => round($proyeccionAnual_AHJR, 2),
            'mayor_gasto' => $mayorGasto_AHJR ? [
                'nombre' => $mayorGasto_AHJR['nombre'],
                'costo' => (float)$mayorGasto_AHJR['costo']
            ] : null,
            'proximo_vencimiento' => null
        ];

        // Formatear próximo vencimiento si existe
        if ($proximoVencimiento_AHJR) {
            $diaActual_AHJR = (int) date('j');
            $mesActual_AHJR = (int) date('n');
            $anioActual_AHJR = (int) date('Y');
            $diaCobro_AHJR = (int) $proximoVencimiento_AHJR['dia_cobro_ahjr'];

            // Calcular fecha de próximo cobro
            if ($diaCobro_AHJR >= $diaActual_AHJR) {
                // Este mes
                $fechaCobro_AHJR = sprintf('%04d-%02d-%02d', $anioActual_AHJR, $mesActual_AHJR, $diaCobro_AHJR);
            } else {
                // Próximo mes
                $fecha_AHJR = new DateTime("first day of next month");
                $fechaCobro_AHJR = sprintf(
                    '%04d-%02d-%02d',
                    (int) $fecha_AHJR->format('Y'),
                    (int) $fecha_AHJR->format('n'),
                    $diaCobro_AHJR
                );
            }

            $resumen_AHJR['proximo_vencimiento'] = [
                'id' => (int) $proximoVencimiento_AHJR['id_suscripcion_ahjr'],
                'nombre_servicio' => $proximoVencimiento_AHJR['nombre_servicio_ahjr'],
                'fecha' => $fechaCobro_AHJR,
                'monto' => (float) $proximoVencimiento_AHJR['costo_ahjr']
            ];
        }

        return $resumen_AHJR;
    }

    /**
     * 1.5 Obtiene Top 3 suscripciones más costosas
     */
    public function obtenerTop3Costosas_AHJR(int $uid_AHJR): array
    {
        $top3_AHJR = $this->model_AHJR->obtenerTopSuscripciones_AHJR($uid_AHJR, 3);

        // Asegurar tipos de datos
        return array_map(function ($item_AHJR) {
            return [
                'nombre' => $item_AHJR['nombre'],
                'costo' => (float) $item_AHJR['costo']
            ];
        }, $top3_AHJR);
    }

    /**
     * 2. Prepara datos para gráfica mensual (Chart.js)
     * 
     * Retorna: { labels: ['Jun', 'Jul', ...], data: [120.50, 135.00, ...] }
     */
    /**
     * 2. Prepara datos para gráfica mensual (Chart.js)
     * 
     * Retorna: { labels: ['Ene 2024', 'Feb 2024', ...], data: [120.50, 135.00, ...] }
     * Rango: Enero a Diciembre del año actual.
     */
    public function prepararDatosGraficaMensual_AHJR(int $uid_AHJR): array
    {
        $historial_AHJR = $this->model_AHJR->obtenerHistorialAnual_AHJR($uid_AHJR);
        $anioActual_AHJR = date('Y');

        $labels_AHJR = [];
        $data_AHJR = [];

        // Iterar de Enero (1) a Diciembre (12)
        for ($m_AHJR = 1; $m_AHJR <= 12; $m_AHJR++) {
            $mesKey_AHJR = sprintf('%s-%02d', $anioActual_AHJR, $m_AHJR);

            // Label: "Ene 2024"
            $dateObj_AHJR = DateTime::createFromFormat('!m', (string)$m_AHJR);
            $mesNombre_AHJR = $this->formatearMesEspanol_AHJR($dateObj_AHJR->format('M'));

            $labels_AHJR[] = "$mesNombre_AHJR $anioActual_AHJR";
            $data_AHJR[] = isset($historial_AHJR[$mesKey_AHJR]) ? round((float)$historial_AHJR[$mesKey_AHJR], 2) : 0;
        }

        // --- LÓGICA SMART START ---
        // Encontrar el primer índice con datos > 0
        $primerIndiceConDatos_AHJR = -1;
        foreach ($data_AHJR as $index_AHJR => $valor_AHJR) {
            if ($valor_AHJR > 0) {
                $primerIndiceConDatos_AHJR = $index_AHJR;
                break;
            }
        }

        if ($primerIndiceConDatos_AHJR !== -1) {
            // Si hay datos, cortar desde el primer mes con actividad
            $labels_AHJR = array_slice($labels_AHJR, $primerIndiceConDatos_AHJR);
            $data_AHJR = array_slice($data_AHJR, $primerIndiceConDatos_AHJR);
        } else {
            // Si NO hay datos (todo 0), mostrar los últimos 3 meses por defecto
            // O hasta el mes actual si estamos a principio de año
            $mesActual_AHJR = (int)date('n'); // 1-12
            $indiceFinal_AHJR = $mesActual_AHJR - 1; // 0-11

            // Queremos mostrar 3 meses terminando en el mes actual
            $indiceInicial_AHJR = max(0, $indiceFinal_AHJR - 2);
            $longitud_AHJR = ($indiceFinal_AHJR - $indiceInicial_AHJR) + 1;

            $labels_AHJR = array_slice($labels_AHJR, $indiceInicial_AHJR, $longitud_AHJR);
            $data_AHJR = array_slice($data_AHJR, $indiceInicial_AHJR, $longitud_AHJR);
        }

        // Re-indexar arrays (opcional pero recomendado para JSON)
        $labels_AHJR = array_values($labels_AHJR);
        $data_AHJR = array_values($data_AHJR);

        return [
            'labels' => $labels_AHJR,
            'data' => $data_AHJR
        ];
    }

    /**
     * 3. Prepara distribución por método de pago (Chart.js)
     * 
     * Retorna: { labels: ['Visa', 'PayPal', ...], data: [450.00, 230.00, ...] }
     */
    public function prepararDistribucionMetodos_AHJR(int $uid_AHJR): array
    {
        $distribucion_AHJR = $this->model_AHJR->obtenerDistribucionPorMetodo_AHJR($uid_AHJR);

        $labels_AHJR = [];
        $data_AHJR = [];

        foreach ($distribucion_AHJR as $row_AHJR) {
            $labels_AHJR[] = $row_AHJR['metodo_pago_ahjr'];
            $data_AHJR[] = round((float) $row_AHJR['total'], 2);
        }

        return [
            'labels' => $labels_AHJR,
            'data' => $data_AHJR
        ];
    }

    /**
     * 4. Prepara distribución por frecuencia (Chart.js)
     * 
     * Retorna: { labels: ['Mensual', 'Anual'], data: [5, 1] }
     */
    public function prepararDistribucionFrecuencia_AHJR(int $uid_AHJR): array
    {
        $distribucion_AHJR = $this->model_AHJR->obtenerDistribucionFrecuencia_AHJR($uid_AHJR);

        // Asegurar orden y valores por defecto
        $mensual_AHJR = isset($distribucion_AHJR['mensual']) ? (int) $distribucion_AHJR['mensual'] : 0;
        $anual_AHJR = isset($distribucion_AHJR['anual']) ? (int) $distribucion_AHJR['anual'] : 0;

        return [
            'labels' => ['Mensual', 'Anual'],
            'data' => [$mensual_AHJR, $anual_AHJR]
        ];
    }

    // ===== MÉTODOS PRIVADOS =====

    /**
     * Formatea abreviatura de mes en español
     */
    private function formatearMesEspanol_AHJR(string $mesIngles_AHJR): string
    {
        $meses_AHJR = [
            'Jan' => 'Ene',
            'Feb' => 'Feb',
            'Mar' => 'Mar',
            'Apr' => 'Abr',
            'May' => 'May',
            'Jun' => 'Jun',
            'Jul' => 'Jul',
            'Aug' => 'Ago',
            'Sep' => 'Sep',
            'Oct' => 'Oct',
            'Nov' => 'Nov',
            'Dec' => 'Dic'
        ];

        return $meses_AHJR[$mesIngles_AHJR] ?? $mesIngles_AHJR;
    }
}
