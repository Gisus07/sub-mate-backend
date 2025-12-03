<?php

declare(strict_types=1);

namespace App\controllers;

use App\services\AuthService;
use App\services\UsuarioService;
use App\services\AlertsService;
use App\core\Response;
use Exception;

/**
 * AuthController - Endpoints Públicos de Autenticación
 * 
 * Responsabilidad: Manejo de HTTP para auth
 */
class AuthController
{
    private AuthService $authService_AHJR;
    private UsuarioService $usuarioService_AHJR;

    public function __construct()
    {
        $this->authService_AHJR = new AuthService();
        $this->usuarioService_AHJR = new UsuarioService();
    }

    /**
     * POST /api/auth/register
     * Registro de nuevo usuario
     */
    public function register_ahjr(): void
    {
        try {
            $input_AHJR = $this->leerJSON_AHJR();

            // Validar campos requeridos
            $requeridos_AHJR = ['nombre', 'apellido', 'email', 'clave'];
            foreach ($requeridos_AHJR as $campo_AHJR) {
                if (empty($input_AHJR[$campo_AHJR])) {
                    Response::badRequest_ahjr('Campos incompletos.');
                }
            }

            // Validar email
            if (!filter_var($input_AHJR['email'], FILTER_VALIDATE_EMAIL)) {
                Response::badRequest_ahjr('Email inválido.');
            }

            $resultado_AHJR = $this->authService_AHJR->registrarUsuario_AHJR($input_AHJR);

            Response::ok_ahjr($resultado_AHJR);
        } catch (Exception $e) {
            $status_AHJR = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status_AHJR);
        }
    }

    /**
     * POST /api/auth/register-verify
     * Verificar OTP y activar cuenta
     */
    public function verifyOTP_ahjr(): void
    {
        try {
            $input_AHJR = $this->leerJSON_AHJR();

            if (empty($input_AHJR['email']) || empty($input_AHJR['otp'])) {
                Response::badRequest_ahjr('Email y OTP requeridos.');
            }

            $resultado_AHJR = $this->authService_AHJR->verificarYActivar_AHJR($input_AHJR['email'], $input_AHJR['otp']);

            // Enviar email de bienvenida
            try {
                $alertsService_AHJR = new AlertsService();
                $alertsService_AHJR->enviarRegistroExitoso_AHJR(['email' => $input_AHJR['email'], 'nombre' => 'Usuario']);
            } catch (Exception $e) {
                // No bloquear el flujo si falla el correo
                error_log("Error enviando email de bienvenida: " . $e->getMessage());
            }

            Response::json_ahjr($resultado_AHJR, 201);
        } catch (Exception $e) {
            $status_AHJR = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status_AHJR);
        }
    }

    /**
     * POST /api/auth/login
     * Inicio de sesión
     * CRÍTICO: Devuelve usuario con campo 'rol'
     */
    public function login_ahjr(): void
    {
        try {
            $input_AHJR = $this->leerJSON_AHJR();

            if (empty($input_AHJR['email']) || empty($input_AHJR['clave'])) {
                Response::badRequest_ahjr('Email y contraseña requeridos.');
            }

            $resultado_AHJR = $this->authService_AHJR->login_AHJR($input_AHJR['email'], $input_AHJR['clave']);

            // Resultado incluye: { "usuario": {..., "rol": "beta"}, "token": "..." }
            Response::ok_ahjr([
                'message' => 'Login exitoso.',
                'usuario' => $resultado_AHJR['usuario'],
                'token' => $resultado_AHJR['token']
            ]);
        } catch (Exception $e) {
            $status_AHJR = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status_AHJR);
        }
    }

    /**
     * POST /api/auth/logout
     * Cerrar sesión y limpiar cookie
     */
    public function logout_ahjr(): void
    {
        // Limpiar cookie
        $secure_AHJR = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        setcookie(
            'sm_session',
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => $secure_AHJR,
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
    public function passwordReset_ahjr(): void
    {
        try {
            $input_AHJR = $this->leerJSON_AHJR();

            if (empty($input_AHJR['email'])) {
                Response::badRequest_ahjr('Email requerido.');
            }

            $resultado_AHJR = $this->authService_AHJR->solicitarResetPassword_AHJR($input_AHJR['email']);

            Response::ok_ahjr($resultado_AHJR);
        } catch (Exception $e) {
            $status_AHJR = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status_AHJR);
        }
    }

    /**
     * POST /api/auth/password-reset-verify
     * Verificar código y cambiar contraseña
     */
    public function passwordResetVerify_ahjr(): void
    {
        try {
            $input_AHJR = $this->leerJSON_AHJR();

            if (empty($input_AHJR['email']) || empty($input_AHJR['otp']) || empty($input_AHJR['clave'])) {
                Response::badRequest_ahjr('Email, OTP y nueva clave requeridos.');
            }

            $resultado_AHJR = $this->authService_AHJR->verificarResetPassword_AHJR($input_AHJR['email'], $input_AHJR['otp'], $input_AHJR['clave']);

            Response::ok_ahjr($resultado_AHJR);
        } catch (Exception $e) {
            $status_AHJR = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status_AHJR);
        }
    }

    /**
     * GET /api/auth/me
     * Obtiene usuario autenticado desde token
     */
    public function me_ahjr(): void
    {
        try {
            $token_AHJR = $this->extraerToken_AHJR();
            $payload_AHJR = $this->authService_AHJR->validarToken_AHJR($token_AHJR);

            // Obtener perfil completo desde DB
            $usuarioCompleto_AHJR = $this->usuarioService_AHJR->obtenerPerfil_AHJR($payload_AHJR['sub']);

            Response::ok_ahjr([
                'data' => [
                    'usuario' => $usuarioCompleto_AHJR,
                    'token' => $token_AHJR
                ]
            ]);
        } catch (Exception $e) {
            $status_AHJR = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status_AHJR);
        }
    }

    /**
     * GET /api/auth/email-available
     * Verifica si un correo electrónico está disponible
     * UX Check: Llamado por el Frontend al salir del campo email
     */
    public function checkEmailAvailability_ahjr(): void
    {
        try {
            // Obtener email de los parámetros de la URL
            $email_AHJR = $_GET['email'] ?? '';

            // Validar que el email no esté vacío
            if (empty($email_AHJR)) {
                Response::badRequest_ahjr('Email es requerido.');
            }

            // Validar formato de email
            if (!filter_var($email_AHJR, FILTER_VALIDATE_EMAIL)) {
                Response::badRequest_ahjr('Formato de email inválido.');
            }

            // Verificar si el email ya existe
            $usuarioModel_AHJR = new \App\models\UsuarioModel();
            $existe_AHJR = $usuarioModel_AHJR->existeEmail_AHJR($email_AHJR);

            if ($existe_AHJR) {
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

    private function extraerToken_AHJR(): string
    {
        $headers_AHJR = getallheaders();
        $auth_AHJR = $headers_AHJR['Authorization'] ?? $headers_AHJR['authorization'] ?? '';

        // 1. Intentar Header
        if (preg_match('/Bearer\s+(.*)$/i', $auth_AHJR, $matches_AHJR)) {
            return $matches_AHJR[1];
        }

        // 2. Intentar Cookie
        if (isset($_COOKIE['sm_session']) && !empty($_COOKIE['sm_session'])) {
            return $_COOKIE['sm_session'];
        }

        throw new Exception('Token requerido.', 401);
    }
}
