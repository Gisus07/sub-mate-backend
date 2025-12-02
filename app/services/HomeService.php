<?php

declare(strict_types=1);

namespace App\services;

use App\models\HomeModel;

class HomeService
{
    private HomeModel $homeModel;

    public function __construct()
    {
        $this->homeModel = new HomeModel();
    }

    public function obtenerDatosHome(int $uid): array
    {
        // 1. Obtener datos individuales
        $gasto7Dias = $this->homeModel->obtenerGastoProximos7Dias($uid);
        $proximoGranCargo = $this->homeModel->obtenerProximoGranCargo($uid);
        $totalSuscripciones = $this->homeModel->obtenerTotalSuscripciones($uid);
        $proximosVencimientos = $this->homeModel->obtenerProximosVencimientos($uid);
        $gastoSemanal = $this->homeModel->obtenerGastoPorSemana($uid);

        // 2. Formatear respuesta con manejo de nulos
        return [
            'status' => 200,
            'success' => true,
            'data' => [
                'semaforo' => [
                    'gasto_7_dias' => round($gasto7Dias, 2),
                    'proximo_gran_cargo' => $proximoGranCargo ? [
                        'nombre' => $proximoGranCargo['nombre'],
                        'monto' => (float) $proximoGranCargo['monto'],
                        'fecha' => $proximoGranCargo['fecha']
                    ] : null,
                    'total_suscripciones' => $totalSuscripciones
                ],
                'proximos_vencimientos' => $proximosVencimientos ?: [],
                'gasto_semanal' => $gastoSemanal
            ]
        ];
    }
}
