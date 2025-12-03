<?php

declare(strict_types=1);

namespace App\controllers;

use App\services\SuscripcionOperacionesService;
use App\core\AuthMiddleware;
use App\core\Response;
use Exception;

/**
 * SuscripcionOperacionesController - Con Middleware y Roles
 */
class SuscripcionOperacionesController
{
    private SuscripcionOperacionesService $service_AHJR;
    private AuthMiddleware $middleware_AHJR;

    public function __construct()
    {
        $this->service_AHJR = new SuscripcionOperacionesService();
        $this->middleware_AHJR = new AuthMiddleware();
    }

    /**
     * 1. PATCH /api/suscripciones/{id}/estado
     */
    public function cambiarEstado(int $id_AHJR): void
    {
        try {
            // Autenticar
            $usuario_AHJR = $this->middleware_AHJR->handle_AHJR();

            $input_AHJR = $this->leerJSON_AHJR();

            if (empty($input_AHJR['estado'])) {
                throw new Exception('Estado requerido.', 400);
            }

            $frecuencia_AHJR = $input_AHJR['frecuencia'] ?? null;
            $costo_AHJR = isset($input_AHJR['costo']) ? (float)$input_AHJR['costo'] : null;

            $this->service_AHJR->gestionarEstado_AHJR($id_AHJR, $input_AHJR['estado'], $usuario_AHJR['sub'], $frecuencia_AHJR, $costo_AHJR);

            Response::ok_ahjr(['message' => 'Estado actualizado correctamente.']);
        } catch (Exception $e) {
            $status_AHJR = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status_AHJR);
        }
    }

    /**
     * 2. POST /api/suscripciones/{id}/simular-pago - Solo beta/admin
     */
    public function simularPago(int $id_AHJR): void
    {
        try {
            // Requiere rol beta o admin
            $usuario_AHJR = $this->middleware_AHJR->requiereRoles_AHJR(['beta', 'admin']);

            $input_AHJR = $this->leerJSON_AHJR();
            $metodo_AHJR = $input_AHJR['metodo_pago'] ?? 'Visa';

            $this->service_AHJR->procesarSimulacionPago_AHJR($id_AHJR, $metodo_AHJR, $usuario_AHJR['sub']);

            Response::ok_ahjr(['message' => 'Pago simulado correctamente.']);
        } catch (Exception $e) {
            $status_AHJR = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status_AHJR);
        }
    }

    // ===== HELPERS PRIVADOS =====

    private function leerJSON_AHJR(): array
    {
        $json_AHJR = file_get_contents('php://input');
        return json_decode($json_AHJR, true) ?? [];
    }
}
