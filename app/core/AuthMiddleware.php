<?php

declare(strict_types=1);

namespace App\core;

use App\services\AuthService;
use Exception;

/**
 * AuthMiddleware - Middleware de Autenticación
 * 
 * RESTRICCIÓN: Máximo 5 métodos públicos
 * Responsabilidad: Validar tokens JWT y proteger rutas
 */
class AuthMiddleware
{
    private AuthService $authService_AHJR;

    public function __construct()
    {
        $this->authService_AHJR = new AuthService();
    }

    /**
     * 1. Maneja la autenticación de la petición
     * Retorna el payload del usuario autenticado
     */
    public function handle_AHJR(): array
    {
        $token_AHJR = $this->extraerToken_AHJR();
        return $this->authService_AHJR->validarToken_AHJR($token_AHJR);
    }

    /**
     * 2. Verifica que el usuario tenga un rol específico
     */
    public function requiereRol_AHJR(string $rolRequerido_AHJR): array
    {
        $usuario_AHJR = $this->handle_AHJR();

        if ($usuario_AHJR['rol'] !== $rolRequerido_AHJR && $usuario_AHJR['rol'] !== 'admin') {
            throw new Exception('Acceso denegado. Rol insuficiente.', 403);
        }

        return $usuario_AHJR;
    }

    /**
     * 3. Verifica que el usuario tenga uno de varios roles permitidos
     */
    public function requiereRoles_AHJR(array $rolesPermitidos_AHJR): array
    {
        $usuario_AHJR = $this->handle_AHJR();

        if (!in_array($usuario_AHJR['rol'], $rolesPermitidos_AHJR) && $usuario_AHJR['rol'] !== 'admin') {
            throw new Exception('Acceso denegado. Rol insuficiente.', 403);
        }

        return $usuario_AHJR;
    }

    // ===== MÉTODOS PRIVADOS =====

    /**
     * Extrae el token JWT del header Authorization
     */
    private function extraerToken_AHJR(): string
    {
        // Compatibilidad CLI y web
        if (!function_exists('getallheaders')) {
            $headers_AHJR = [];
            foreach ($_SERVER as $name_AHJR => $value_AHJR) {
                if (substr($name_AHJR, 0, 5) == 'HTTP_') {
                    $headers_AHJR[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name_AHJR, 5)))))] = $value_AHJR;
                }
            }
        } else {
            $headers_AHJR = getallheaders();
        }

        $authHeader_AHJR = $headers_AHJR['Authorization'] ?? $headers_AHJR['authorization'] ?? '';

        // 1. Intentar Header Authorization
        if (!empty($authHeader_AHJR)) {
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader_AHJR, $matches_AHJR)) {
                return $matches_AHJR[1];
            }
        }

        // 2. Intentar Cookie (Dual Auth)
        if (isset($_COOKIE['sm_session']) && !empty($_COOKIE['sm_session'])) {
            return $_COOKIE['sm_session'];
        }

        throw new Exception('Token de autenticación requerido.', 401);
    }
}
