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
    private UsuarioService $usuarioService_AHJR;
    private AuthService $authService_AHJR;

    public function __construct()
    {
        $this->usuarioService_AHJR = new UsuarioService();
        $this->authService_AHJR = new AuthService();
    }

    /**
     * PUT /api/usuario/update
     * Actualiza perfil del usuario autenticado
     */
    public function update(): void
    {
        try {
            $usuarioAuth_AHJR = $this->obtenerUsuarioAutenticado_AHJR();
            $input_AHJR = $this->leerJSON_AHJR();

            // Validar email si se envía
            if (isset($input_AHJR['email']) && !filter_var($input_AHJR['email'], FILTER_VALIDATE_EMAIL)) {
                Response::badRequest_ahjr('Email inválido.');
            }

            $this->usuarioService_AHJR->editarPerfil_AHJR($usuarioAuth_AHJR['sub'], $input_AHJR);

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
            $usuarioAuth_AHJR = $this->obtenerUsuarioAutenticado_AHJR();

            // Usar el modelo directamente para eliminar
            $model_AHJR = new \App\models\UsuarioModel();
            $model_AHJR->eliminar_AHJR($usuarioAuth_AHJR['sub']);

            Response::ok_ahjr(['message' => 'Cuenta eliminada correctamente.']);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
        }
    }

    /**
     * PATCH /api/perfil/password
     * Cambia la contraseña del usuario autenticado
     */
    public function updatePassword(): void
    {
        try {
            $usuarioAuth_AHJR = $this->obtenerUsuarioAutenticado_AHJR();
            $input_AHJR = $this->leerJSON_AHJR();

            // Validar campos requeridos
            if (empty($input_AHJR['clave_actual']) || empty($input_AHJR['clave_nueva'])) {
                Response::badRequest_ahjr('Los campos clave_actual y clave_nueva son obligatorios.');
            }

            // Validar longitud mínima
            if (strlen($input_AHJR['clave_nueva']) < 8) {
                Response::badRequest_ahjr('La nueva contraseña debe tener al menos 8 caracteres.');
            }

            $this->usuarioService_AHJR->cambiarClave_AHJR(
                $usuarioAuth_AHJR['sub'],
                $input_AHJR['clave_actual'],
                $input_AHJR['clave_nueva']
            );

            Response::ok_ahjr(['message' => 'Contraseña actualizada correctamente.']);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
        }
    }

    // ===== HELPERS PRIVADOS =====

    private function obtenerUsuarioAutenticado_AHJR(): array
    {
        $headers_AHJR = getallheaders();
        $auth_AHJR = $headers_AHJR['Authorization'] ?? $headers_AHJR['authorization'] ?? '';

        if (!preg_match('/Bearer\s+(.*)$/i', $auth_AHJR, $matches_AHJR)) {
            throw new Exception('Token requerido.', 401);
        }

        return $this->authService_AHJR->validarToken_AHJR($matches_AHJR[1]);
    }

    private function leerJSON_AHJR(): array
    {
        $json_AHJR = file_get_contents('php://input');
        return json_decode($json_AHJR, true) ?? [];
    }
}
