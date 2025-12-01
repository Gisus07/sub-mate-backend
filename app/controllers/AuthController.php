<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\UsuarioService;
use App\Core\Response;
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
                    Response::badRequest_ahjr('Campos incompletos.');
                }
            }

            // Validar email
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                Response::badRequest_ahjr('Email inválido.');
            }

            $resultado = $this->authService->registrarUsuario($input);

            Response::ok_ahjr($resultado);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
        }
    }

    /**
     * POST /api/auth/register-verify
     * Verificar OTP y activar cuenta
     */
    public function verifyOTP(): void
    {
        try {
            $input = $this->leerJSON();

            if (empty($input['email']) || empty($input['otp'])) {
                Response::badRequest_ahjr('Email y OTP requeridos.');
            }

            $resultado = $this->authService->verificarYActivar($input['email'], $input['otp']);

            Response::json_ahjr($resultado, 201);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
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
                Response::badRequest_ahjr('Email y contraseña requeridos.');
            }

            $resultado = $this->authService->login($input['email'], $input['clave']);

            // Resultado incluye: { "usuario": {..., "rol": "beta"}, "token": "..." }
            Response::ok_ahjr([
                'message' => 'Login exitoso.',
                'usuario' => $resultado['usuario'],
                'token' => $resultado['token']
            ]);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
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

        Response::ok_ahjr(['message' => 'Sesión cerrada correctamente.']);
    }

    /**
     * POST /api/auth/password-reset
     * Solicitar reset de contraseña
     */
    public function passwordReset(): void
    {
        try {
            $input = $this->leerJSON();

            if (empty($input['email'])) {
                Response::badRequest_ahjr('Email requerido.');
            }

            $resultado = $this->authService->solicitarResetPassword($input['email']);

            Response::ok_ahjr($resultado);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
        }
    }

    /**
     * POST /api/auth/password-reset-verify
     * Verificar código y cambiar contraseña
     */
    public function passwordResetVerify(): void
    {
        try {
            $input = $this->leerJSON();

            if (empty($input['email']) || empty($input['otp']) || empty($input['clave'])) {
                Response::badRequest_ahjr('Email, OTP y nueva clave requeridos.');
            }

            $resultado = $this->authService->verificarResetPassword($input['email'], $input['otp'], $input['clave']);

            Response::ok_ahjr($resultado);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
        }
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

            Response::ok_ahjr([
                'data' => [
                    'usuario' => $usuarioCompleto,
                    'token' => $token
                ]
            ]);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
        }
    }

    /**
     * GET /api/auth/email-available
     * Verifica si un correo electrónico está disponible
     * UX Check: Llamado por el Frontend al salir del campo email
     */
    public function checkEmailAvailability(): void
    {
        try {
            // Obtener email de los parámetros de la URL
            $email = $_GET['email'] ?? '';

            // Validar que el email no esté vacío
            if (empty($email)) {
                Response::badRequest_ahjr('Email es requerido.');
            }

            // Validar formato de email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Response::badRequest_ahjr('Formato de email inválido.');
            }

            // Verificar si el email ya existe
            $usuarioModel = new \App\Models\UsuarioModel();
            $existe = $usuarioModel->existeEmail($email);

            if ($existe) {
                // Email NO disponible - 409 Conflict
                Response::json_ahjr([
                    'available' => false,
                    'message' => 'Este correo ya está en uso.'
                ], 409);
            } else {
                // Email disponible - 200 OK
                Response::ok_ahjr([
                    'available' => true,
                    'message' => 'Correo disponible'
                ]);
            }
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
}
