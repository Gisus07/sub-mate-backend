<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SuscripcionService;
use App\Core\AuthMiddleware;
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

            $this->responder(['suscripciones' => $suscripciones]);
        } catch (Exception $e) {
            $this->manejarError($e);
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

            $this->responder([
                'message' => 'Suscripción creada exitosamente.',
                'id' => $resultado['id']
            ], 201);
        } catch (Exception $e) {
            $this->manejarError($e);
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

            $this->responder(['suscripcion' => $suscripcion]);
        } catch (Exception $e) {
            $this->manejarError($e);
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

            $this->responder(['message' => 'Suscripción actualizada correctamente.']);
        } catch (Exception $e) {
            $this->manejarError($e);
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

            $this->responder(['message' => 'Suscripción eliminada correctamente.']);
        } catch (Exception $e) {
            $this->manejarError($e);
        }
    }

    // ===== HELPERS PRIVADOS =====

    private function leerJSON(): array
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }

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
