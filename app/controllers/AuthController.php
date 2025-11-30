<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\UsuarioService;
use Exception;

/**
 * AuthController - Endpoints Públicos de Autenticación
 * 
 * Responsabilidad: Manejo de HTTP para auth
 */
class AuthController
{
    private AuthService $authService;
    private UsuarioService $usuarioService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->usuarioService = new UsuarioService();
    }

    /**
     * POST /api/auth/register
     * Registro de nuevo usuario
     */
    public function register(): void
    {
        try {
            $input = $this->leerJSON();

            // Validar campos requeridos
            $requeridos = ['nombre', 'apellido', 'email', 'clave'];
            foreach ($requeridos as $campo) {
                if (empty($input[$campo])) {
                    $this->responder(['error' => 'Campos incompletos.'], 400);
                    return;
                }
            }

            // Validar email
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                $this->responder(['error' => 'Email inválido.'], 400);
                return;
            }

            $resultado = $this->authService->registrarUsuario($input);

            $this->responder([
                'message' => 'Usuario registrado exitosamente.',
                'id' => $resultado['id']
            ], 201);
        } catch (Exception $e) {
            $this->manejarError($e);
        }
    }

    /**
     * POST /api/auth/login
     * Inicio de sesión
     * CRÍTICO: Devuelve usuario con campo 'rol'
     */
    public function login(): void
    {
        try {
            $input = $this->leerJSON();

            if (empty($input['email']) || empty($input['clave'])) {
                $this->responder(['error' => 'Email y contraseña requeridos.'], 400);
                return;
            }

            $resultado = $this->authService->login($input['email'], $input['clave']);

            // Resultado incluye: { "usuario": {..., "rol": "beta"}, "token": "..." }
            $this->responder([
                'message' => 'Login exitoso.',
                'usuario' => $resultado['usuario'],  // Incluye 'rol'
                'token' => $resultado['token']
            ]);
        } catch (Exception $e) {
            $this->manejarError($e);
        }
    }

    /**
     * POST /api/auth/logout
     * Cerrar sesión y limpiar cookie
     */
    public function logout(): void
    {
        // Limpiar cookie
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        setcookie(
            'sm_session',
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

        $this->responder(['message' => 'Sesión cerrada correctamente.']);
    }

    /**
     * GET /api/auth/me
     * Obtiene usuario autenticado desde token
     */
    public function me(): void
    {
        try {
            $token = $this->extraerToken();
            $payload = $this->authService->validarToken($token);

            // Obtener perfil completo desde DB
            $usuarioCompleto = $this->usuarioService->obtenerPerfil($payload['sub']);

            $this->responder([
                'status' => 200,
                'success' => true,
                'data' => [
                    'usuario' => $usuarioCompleto,
                    'token' => $token
                ]
            ]);
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

    private function extraerToken(): string
    {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        // 1. Intentar Header
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }

        // 2. Intentar Cookie
        if (isset($_COOKIE['sm_session']) && !empty($_COOKIE['sm_session'])) {
            return $_COOKIE['sm_session'];
        }

        throw new Exception('Token requerido.', 401);
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
