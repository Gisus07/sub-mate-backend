<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\DashboardService;
use App\Core\AuthMiddleware;
use Exception;

/**
 * DashboardController - API para Dashboard/Analytics
 * 
 * Responsabilidad: Consolidar todos los datos del dashboard en un solo endpoint
 */
class DashboardController
{
    private DashboardService $service;
    private AuthMiddleware $middleware;

    public function __construct()
    {
        $this->service = new DashboardService();
        $this->middleware = new AuthMiddleware();
    }

    /**
     * 1. GET /api/dashboard - Retorna payload completo para el frontend
     */
    public function index(): void
    {
        try {
            // Autenticar usuario
            $usuario = $this->middleware->handle();
            $uid = $usuario['sub'];

            // Consolidar todos los datos
            $payload = [
                'resumen' => $this->service->generarResumen($uid),
                'grafica_mensual' => $this->service->prepararDatosGraficaMensual($uid),
                'distribucion_metodos' => $this->service->prepararDistribucionMetodos($uid)
            ];

            $this->responder($payload);
        } catch (Exception $e) {
            $this->manejarError($e);
        }
    }

    // ===== HELPERS PRIVADOS =====

    private function responder(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    private function manejarError(Exception $e): void
    {
        $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
        $this->responder(['error' => $e->getMessage()], $status);
    }
}
