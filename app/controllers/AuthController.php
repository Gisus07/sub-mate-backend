<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use Exception;

/**
 * AuthController - Endpoints Públicos de Autenticación
 * 
 * Responsabilidad: Manejo de HTTP para auth
 */
class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
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
     * GET /api/auth/me
     * Obtiene usuario autenticado desde token
     */
    public function me(): void
    {
        try {
            $token = $this->extraerToken();
            $payload = $this->authService->validarToken($token);

            $this->responder([
                'usuario' => [
                    'id' => $payload['sub'],
                    'email' => $payload['email'],
                    'rol' => $payload['rol']  // Incluido en el JWT
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

        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
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
