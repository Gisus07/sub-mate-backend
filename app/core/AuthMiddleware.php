<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\AuthService;
use Exception;

/**
 * AuthMiddleware - Middleware de Autenticación
 * 
 * RESTRICCIÓN: Máximo 5 métodos públicos
 * Responsabilidad: Validar tokens JWT y proteger rutas
 */
class AuthMiddleware
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * 1. Maneja la autenticación de la petición
     * Retorna el payload del usuario autenticado
     */
    public function handle(): array
    {
        $token = $this->extraerToken();
        return $this->authService->validarToken($token);
    }

    /**
     * 2. Verifica que el usuario tenga un rol específico
     */
    public function requiereRol(string $rolRequerido): array
    {
        $usuario = $this->handle();

        if ($usuario['rol'] !== $rolRequerido && $usuario['rol'] !== 'admin') {
            throw new Exception('Acceso denegado. Rol insuficiente.', 403);
        }

        return $usuario;
    }

    /**
     * 3. Verifica que el usuario tenga uno de varios roles permitidos
     */
    public function requiereRoles(array $rolesPermitidos): array
    {
        $usuario = $this->handle();

        if (!in_array($usuario['rol'], $rolesPermitidos) && $usuario['rol'] !== 'admin') {
            throw new Exception('Acceso denegado. Rol insuficiente.', 403);
        }

        return $usuario;
    }

    // ===== MÉTODOS PRIVADOS =====

    /**
     * Extrae el token JWT del header Authorization
     */
    private function extraerToken(): string
    {
        // Compatibilidad CLI y web
        if (!function_exists('getallheaders')) {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        } else {
            $headers = getallheaders();
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        // 1. Intentar Header Authorization
        if (!empty($authHeader)) {
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }

        // 2. Intentar Cookie (Dual Auth)
        if (isset($_COOKIE['sm_session']) && !empty($_COOKIE['sm_session'])) {
            return $_COOKIE['sm_session'];
        }

        throw new Exception('Token de autenticación requerido.', 401);
    }
}
