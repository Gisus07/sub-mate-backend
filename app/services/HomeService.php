<?php

declare(strict_types=1);

namespace App\services;

use App\models\HomeModel;

class HomeService
{
    private HomeModel $homeModel_AHJR;

    public function __construct()
    {
        $this->homeModel_AHJR = new HomeModel();
    }

    public function obtenerDatosHome_AHJR(int $uid_AHJR): array
    {
        // 1. Obtener datos individuales
        $gasto7Dias_AHJR = $this->homeModel_AHJR->obtenerGastoProximos7Dias_AHJR($uid_AHJR);
        $proximoGranCargo_AHJR = $this->homeModel_AHJR->obtenerProximoGranCargo_AHJR($uid_AHJR);
        $totalSuscripciones_AHJR = $this->homeModel_AHJR->obtenerTotalSuscripciones_AHJR($uid_AHJR);
        $proximosVencimientos_AHJR = $this->homeModel_AHJR->obtenerProximosVencimientos_AHJR($uid_AHJR);
        $gastoSemanal_AHJR = $this->homeModel_AHJR->obtenerGastoPorSemana_AHJR($uid_AHJR);

        // 2. Formatear respuesta con manejo de nulos
        return [
            'status' => 200,
            'success' => true,
            'data' => [
                'semaforo' => [
                    'gasto_7_dias' => round($gasto7Dias_AHJR, 2),
                    'proximo_gran_cargo' => $proximoGranCargo_AHJR ? [
                        'nombre' => $proximoGranCargo_AHJR['nombre'],
                        'monto' => (float) $proximoGranCargo_AHJR['monto'],
                        'fecha' => $proximoGranCargo_AHJR['fecha']
                    ] : null,
                    'total_suscripciones' => $totalSuscripciones_AHJR
                ],
                'proximos_vencimientos' => $proximosVencimientos_AHJR ?: [],
                'gasto_semanal' => $gastoSemanal_AHJR
            ]
        ];
    }
}
