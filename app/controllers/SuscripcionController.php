<?php

declare(strict_types=1);

namespace App\controllers;

use App\services\SuscripcionService;
use App\core\AuthMiddleware;
use App\core\Response;
use Exception;

/**
 * SuscripcionController - API REST con Middleware
 * 
 * EJEMPLO DE INTEGRACIÓN DEL MIDDLEWARE
 */
class SuscripcionController
{
    private SuscripcionService $service_AHJR;
    private AuthMiddleware $middleware_AHJR;

    public function __construct()
    {
        $this->service_AHJR = new SuscripcionService();
        $this->middleware_AHJR = new AuthMiddleware();
    }

    /**
     * 1. GET /api/suscripciones - Lista todas
     */
    public function index(): void
    {
        try {
            // Autenticar usuario
            $usuario_AHJR = $this->middleware_AHJR->handle_AHJR();

            // Usar el ID del usuario autenticado
            $suscripciones_AHJR = $this->service_AHJR->obtenerLista_AHJR($usuario_AHJR['sub']);

            Response::ok_ahjr(['suscripciones' => $suscripciones_AHJR]);
        } catch (Exception $e) {
            $status_AHJR = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status_AHJR);
        }
    }

    /**
     * 2. POST /api/suscripciones - Crea nueva
     */
    public function store(): void
    {
        try {
            $usuario_AHJR = $this->middleware_AHJR->handle_AHJR();
            $input_AHJR = $this->leerJSON_AHJR();

            $resultado_AHJR = $this->service_AHJR->crear_AHJR($input_AHJR, $usuario_AHJR['sub']);

            Response::json_ahjr([
                'message' => 'Suscripción creada exitosamente.',
                'id' => $resultado_AHJR['id']
            ], 201);
        } catch (Exception $e) {
            $status_AHJR = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status_AHJR);
        }
    }

    /**
     * 3. GET /api/suscripciones/{id} - Obtiene detalle
     */
    public function show(int $id_AHJR): void
    {
        try {
            $usuario_AHJR = $this->middleware_AHJR->handle_AHJR();
            $suscripcion_AHJR = $this->service_AHJR->obtenerDetalle_AHJR($id_AHJR, $usuario_AHJR['sub']);

            Response::ok_ahjr(['suscripcion' => $suscripcion_AHJR]);
        } catch (Exception $e) {
            $status_AHJR = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status_AHJR);
        }
    }

    /**
     * 4. PUT /api/suscripciones/{id} - Actualiza
     */
    public function update(int $id_AHJR): void
    {
        try {
            $usuario_AHJR = $this->middleware_AHJR->handle_AHJR();
            $input_AHJR = $this->leerJSON_AHJR();

            $this->service_AHJR->modificar_AHJR($id_AHJR, $input_AHJR, $usuario_AHJR['sub']);

            Response::ok_ahjr(['message' => 'Suscripción actualizada correctamente.']);
        } catch (Exception $e) {
            $status_AHJR = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status_AHJR);
        }
    }

    /**
     * 5. DELETE /api/suscripciones/{id} - Elimina
     */
    public function destroy(int $id_AHJR): void
    {
        try {
            $usuario_AHJR = $this->middleware_AHJR->handle_AHJR();

            $this->service_AHJR->borrar_AHJR($id_AHJR, $usuario_AHJR['sub']);

            Response::ok_ahjr(['message' => 'Suscripción eliminada correctamente.']);
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
