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
        $proximoVencimiento = $this->model->obtenerProximoVencimiento($uid);

        $resumen = [
            'total_activas' => $totalActivas,
            'gasto_mes_actual' => round($gastoMesActual, 2),
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
     * 2. Prepara datos para gráfica mensual (Chart.js)
     * 
     * Retorna: { labels: ['Jun', 'Jul', ...], data: [120.50, 135.00, ...] }
     */
    public function prepararDatosGraficaMensual(int $uid): array
    {
        $historial = $this->model->obtenerHistorialUltimos6Meses($uid);

        $labels = [];
        $data = [];

        // Generar últimos 6 meses (incluso si no hay datos)
        for ($i = 5; $i >= 0; $i--) {
            $fecha = new DateTime();
            $fecha->modify("-{$i} months");

            $mesKey = $fecha->format('Y-m');
            $mesLabel = $this->formatearMesEspanol($fecha->format('M')) . ' ' . $fecha->format('Y');

            $labels[] = $mesLabel;
            $data[] = isset($historial[$mesKey]) ? round($historial[$mesKey], 2) : 0;
        }

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
            $labels[] = $row['metodo'];
            $data[] = round((float) $row['total'], 2);
        }

        return [
            'labels' => $labels,
            'data' => $data
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
