<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SuscripcionOperacionesService;
use App\Core\AuthMiddleware;
use App\Core\Response;
use Exception;

/**
 * SuscripcionOperacionesController - Con Middleware y Roles
 */
class SuscripcionOperacionesController
{
    private SuscripcionOperacionesService $service;
    private AuthMiddleware $middleware;

    public function __construct()
    {
        $this->service = new SuscripcionOperacionesService();
        $this->middleware = new AuthMiddleware();
    }

    /**
     * 1. PATCH /api/suscripciones/{id}/estado
     */
    public function cambiarEstado(int $id): void
    {
        try {
            // Autenticar
            $usuario = $this->middleware->handle();

            $input = $this->leerJSON();

            if (empty($input['estado'])) {
                throw new Exception('Estado requerido.', 400);
            }

            $frecuencia = $input['frecuencia'] ?? null;
            $costo = isset($input['costo']) ? (float)$input['costo'] : null;

            $this->service->gestionarEstado($id, $input['estado'], $usuario['sub'], $frecuencia, $costo);

            Response::ok_ahjr(['message' => 'Estado actualizado correctamente.']);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
        }
    }

    /**
     * 2. POST /api/suscripciones/{id}/simular-pago - Solo beta/admin
     */
    public function simularPago(int $id): void
    {
        try {
            // Requiere rol beta o admin
            $usuario = $this->middleware->requiereRoles(['beta', 'admin']);

            $input = $this->leerJSON();
            $metodo = $input['metodo_pago'] ?? 'Visa';

            $this->service->procesarSimulacionPago($id, $metodo, $usuario['sub']);

            Response::ok_ahjr(['message' => 'Pago simulado correctamente.']);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
        }
    }

    // ===== HELPERS PRIVADOS =====

    private function leerJSON(): array
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
}
