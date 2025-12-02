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
    private DashboardModel $model;

    public function __construct()
    {
        $this->model = new DashboardModel();
    }

    /**
     * 1. Genera resumen con KPIs principales
     */
    public function generarResumen(int $uid): array
    {
        $mesActual = (int) date('n');
        $anioActual = (int) date('Y');

        $totalActivas = $this->model->contarActivas($uid);
        $gastoMesActual = $this->model->obtenerGastoTotalMes($uid, $mesActual, $anioActual);
        $gastoEstimado = $this->model->obtenerGastoMensualEstimado($uid);
        $proximoVencimiento = $this->model->obtenerProximoVencimiento($uid);

        // Nuevos KPIs
        $proyeccionAnual = $gastoEstimado * 12;
        $mayorGastoArr = $this->model->obtenerTopSuscripciones($uid, 1);
        $mayorGasto = !empty($mayorGastoArr) ? $mayorGastoArr[0] : null;

        $resumen = [
            'total_activas' => $totalActivas,
            'gasto_mes_actual' => round($gastoMesActual, 2),
            'gasto_mensual_estimado' => round($gastoEstimado, 2),
            'proyeccion_anual' => round($proyeccionAnual, 2),
            'mayor_gasto' => $mayorGasto ? [
                'nombre' => $mayorGasto['nombre'],
                'costo' => (float)$mayorGasto['costo']
            ] : null,
            'proximo_vencimiento' => null
        ];

        // Formatear próximo vencimiento si existe
        if ($proximoVencimiento) {
            $diaActual = (int) date('j');
            $mesActual = (int) date('n');
            $anioActual = (int) date('Y');
            $diaCobro = (int) $proximoVencimiento['dia_cobro_ahjr'];

            // Calcular fecha de próximo cobro
            if ($diaCobro >= $diaActual) {
                // Este mes
                $fechaCobro = sprintf('%04d-%02d-%02d', $anioActual, $mesActual, $diaCobro);
            } else {
                // Próximo mes
                $fecha = new DateTime("first day of next month");
                $fechaCobro = sprintf(
                    '%04d-%02d-%02d',
                    (int) $fecha->format('Y'),
                    (int) $fecha->format('n'),
                    $diaCobro
                );
            }

            $resumen['proximo_vencimiento'] = [
                'id' => (int) $proximoVencimiento['id_suscripcion_ahjr'],
                'nombre_servicio' => $proximoVencimiento['nombre_servicio_ahjr'],
                'fecha' => $fechaCobro,
                'monto' => (float) $proximoVencimiento['costo_ahjr']
            ];
        }

        return $resumen;
    }

    /**
     * 1.5 Obtiene Top 3 suscripciones más costosas
     */
    public function obtenerTop3Costosas(int $uid): array
    {
        $top3 = $this->model->obtenerTopSuscripciones($uid, 3);

        // Asegurar tipos de datos
        return array_map(function ($item) {
            return [
                'nombre' => $item['nombre'],
                'costo' => (float) $item['costo']
            ];
        }, $top3);
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
    public function prepararDatosGraficaMensual(int $uid): array
    {
        $historial = $this->model->obtenerHistorialAnual($uid);
        $anioActual = date('Y');

        $labels = [];
        $data = [];

        // Iterar de Enero (1) a Diciembre (12)
        for ($m = 1; $m <= 12; $m++) {
            $mesKey = sprintf('%s-%02d', $anioActual, $m);

            // Label: "Ene 2024"
            $dateObj = DateTime::createFromFormat('!m', (string)$m);
            $mesNombre = $this->formatearMesEspanol($dateObj->format('M'));

            $labels[] = "$mesNombre $anioActual";
            $data[] = isset($historial[$mesKey]) ? round((float)$historial[$mesKey], 2) : 0;
        }

        // --- LÓGICA SMART START ---
        // Encontrar el primer índice con datos > 0
        $primerIndiceConDatos = -1;
        foreach ($data as $index => $valor) {
            if ($valor > 0) {
                $primerIndiceConDatos = $index;
                break;
            }
        }

        if ($primerIndiceConDatos !== -1) {
            // Si hay datos, cortar desde el primer mes con actividad
            $labels = array_slice($labels, $primerIndiceConDatos);
            $data = array_slice($data, $primerIndiceConDatos);
        } else {
            // Si NO hay datos (todo 0), mostrar los últimos 3 meses por defecto
            // O hasta el mes actual si estamos a principio de año
            $mesActual = (int)date('n'); // 1-12
            $indiceFinal = $mesActual - 1; // 0-11

            // Queremos mostrar 3 meses terminando en el mes actual
            $indiceInicial = max(0, $indiceFinal - 2);
            $longitud = ($indiceFinal - $indiceInicial) + 1;

            $labels = array_slice($labels, $indiceInicial, $longitud);
            $data = array_slice($data, $indiceInicial, $longitud);
        }

        // Re-indexar arrays (opcional pero recomendado para JSON)
        $labels = array_values($labels);
        $data = array_values($data);

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    /**
     * 3. Prepara distribución por método de pago (Chart.js)
     * 
     * Retorna: { labels: ['Visa', 'PayPal', ...], data: [450.00, 230.00, ...] }
     */
    public function prepararDistribucionMetodos(int $uid): array
    {
        $distribucion = $this->model->obtenerDistribucionPorMetodo($uid);

        $labels = [];
        $data = [];

        foreach ($distribucion as $row) {
            $labels[] = $row['metodo_pago_ahjr'];
            $data[] = round((float) $row['total'], 2);
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    /**
     * 4. Prepara distribución por frecuencia (Chart.js)
     * 
     * Retorna: { labels: ['Mensual', 'Anual'], data: [5, 1] }
     */
    public function prepararDistribucionFrecuencia(int $uid): array
    {
        $distribucion = $this->model->obtenerDistribucionFrecuencia($uid);

        // Asegurar orden y valores por defecto
        $mensual = isset($distribucion['mensual']) ? (int) $distribucion['mensual'] : 0;
        $anual = isset($distribucion['anual']) ? (int) $distribucion['anual'] : 0;

        return [
            'labels' => ['Mensual', 'Anual'],
            'data' => [$mensual, $anual]
        ];
    }

    // ===== MÉTODOS PRIVADOS =====

    /**
     * Formatea abreviatura de mes en español
     */
    private function formatearMesEspanol(string $mesIngles): string
    {
        $meses = [
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

        return $meses[$mesIngles] ?? $mesIngles;
    }
}
