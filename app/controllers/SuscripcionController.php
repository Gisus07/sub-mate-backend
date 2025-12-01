<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SuscripcionService;
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
    private SuscripcionService $service;
    private AuthMiddleware $middleware;

    public function __construct()
    {
        $this->service = new SuscripcionService();
        $this->middleware = new AuthMiddleware();
    }

    /**
     * 1. GET /api/suscripciones - Lista todas
     */
    public function index(): void
    {
        try {
            // Autenticar usuario
            $usuario = $this->middleware->handle();

            // Usar el ID del usuario autenticado
            $suscripciones = $this->service->obtenerLista($usuario['sub']);

            Response::ok_ahjr(['suscripciones' => $suscripciones]);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
        }
    }

    /**
     * 2. POST /api/suscripciones - Crea nueva
     */
    public function store(): void
    {
        try {
            $usuario = $this->middleware->handle();
            $input = $this->leerJSON();

            $resultado = $this->service->crear($input, $usuario['sub']);

            Response::json_ahjr([
                'message' => 'Suscripción creada exitosamente.',
                'id' => $resultado['id']
            ], 201);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
        }
    }

    /**
     * 3. GET /api/suscripciones/{id} - Obtiene detalle
     */
    public function show(int $id): void
    {
        try {
            $usuario = $this->middleware->handle();
            $suscripcion = $this->service->obtenerDetalle($id, $usuario['sub']);

            Response::ok_ahjr(['suscripcion' => $suscripcion]);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
        }
    }

    /**
     * 4. PUT /api/suscripciones/{id} - Actualiza
     */
    public function update(int $id): void
    {
        try {
            $usuario = $this->middleware->handle();
            $input = $this->leerJSON();

            $this->service->modificar($id, $input, $usuario['sub']);

            Response::ok_ahjr(['message' => 'Suscripción actualizada correctamente.']);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
        }
    }

    /**
     * 5. DELETE /api/suscripciones/{id} - Elimina
     */
    public function destroy(int $id): void
    {
        try {
            $usuario = $this->middleware->handle();

            $this->service->borrar($id, $usuario['sub']);

            Response::ok_ahjr(['message' => 'Suscripción eliminada correctamente.']);
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
