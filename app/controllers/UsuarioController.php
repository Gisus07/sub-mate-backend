<?php

declare(strict_types=1);

namespace App\controllers;

use App\services\UsuarioService;
use App\services\AuthService;
use App\core\Response;
use Exception;

/**
 * UsuarioController - Endpoints Protegidos de Usuario
 * 
 * Responsabilidad: Gestión de perfil (requiere autenticación)
 */
class UsuarioController
{
    private UsuarioService $usuarioService;
    private AuthService $authService;

    public function __construct()
    {
        $this->usuarioService = new UsuarioService();
        $this->authService = new AuthService();
    }

    /**
     * PUT /api/usuario/update
     * Actualiza perfil del usuario autenticado
     */
    public function update(): void
    {
        try {
            $usuarioAuth = $this->obtenerUsuarioAutenticado();
            $input = $this->leerJSON();

            // Validar email si se envía
            if (isset($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                Response::badRequest_ahjr('Email inválido.');
            }

            $this->usuarioService->editarPerfil($usuarioAuth['sub'], $input);

            Response::ok_ahjr(['message' => 'Perfil actualizado correctamente.']);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
        }
    }

    /**
     * DELETE /api/usuario/delete
     * Elimina cuenta del usuario autenticado
     */
    public function delete(): void
    {
        try {
            $usuarioAuth = $this->obtenerUsuarioAutenticado();

            // Usar el modelo directamente para eliminar
            $model = new \App\models\UsuarioModel();
            $model->eliminar($usuarioAuth['sub']);

            Response::ok_ahjr(['message' => 'Cuenta eliminada correctamente.']);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
        }
    }

    // ===== HELPERS PRIVADOS =====

    private function obtenerUsuarioAutenticado(): array
    {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            throw new Exception('Token requerido.', 401);
        }

        return $this->authService->validarToken($matches[1]);
    }

    private function leerJSON(): array
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
}
