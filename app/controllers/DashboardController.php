<?php

declare(strict_types=1);

namespace App\controllers;

use App\services\DashboardService;
use App\core\AuthMiddleware;
use App\core\Response;
use Exception;

/**
 * DashboardController - API para Dashboard/Analytics
 * 
 * Responsabilidad: Consolidar todos los datos del dashboard en un solo endpoint
 */
class DashboardController
{
    private DashboardService $service_AHJR;
    private AuthMiddleware $middleware_AHJR;

    public function __construct()
    {
        $this->service_AHJR = new DashboardService();
        $this->middleware_AHJR = new AuthMiddleware();
    }

    /**
     * 1. GET /api/dashboard - Retorna payload completo para el frontend
     */
    public function index(): void
    {
        try {
            // Autenticar usuario
            $usuario_AHJR = $this->middleware_AHJR->handle_AHJR();
            $uid_AHJR = $usuario_AHJR['sub'];

            // Consolidar todos los datos
            $payload_AHJR = [
                'resumen' => $this->service_AHJR->generarResumen_AHJR($uid_AHJR),
                'grafica_mensual' => $this->service_AHJR->prepararDatosGraficaMensual_AHJR($uid_AHJR),
                'distribucion' => $this->service_AHJR->prepararDistribucionFrecuencia_AHJR($uid_AHJR),
                'distribucion_metodos' => $this->service_AHJR->prepararDistribucionMetodos_AHJR($uid_AHJR),
                'top_3_costosas' => $this->service_AHJR->obtenerTop3Costosas_AHJR($uid_AHJR)
            ];

            Response::ok_ahjr($payload_AHJR);
        } catch (Exception $e) {
            $status_AHJR = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status_AHJR);
        }
    }

    // ===== HELPERS PRIVADOS =====

}
