<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\UsuarioModel;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * AuthService - Lógica de Autenticación
 * 
 * RESTRICCIÓN: Máximo 5 métodos públicos
 * Responsabilidad: Login, Registro, JWT
 */
class AuthService
{
    private UsuarioModel $model;
    private string $jwtSecret;
    private int $jwtExpiration = 86400; // 24 horas

    public function __construct()
    {
        $this->model = new UsuarioModel();
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'default_secret_change_me';
    }

    /**
     * 1. Registra nuevo usuario
     * Entrada: datos limpios { "nombre": "Juan", "email": "..." }
     * Salida: { "id": 1 }
     */
    public function registrarUsuario(array $datosLimpios): array
    {
        // Validar email único
        if ($this->model->buscarPorEmail($datosLimpios['email'])) {
            throw new Exception('Este correo ya está registrado.', 409);
        }

        // Mapear a formato DB y hashear contraseña
        $datosMapeados = [
            'nombre' => $datosLimpios['nombre'],
            'apellido' => $datosLimpios['apellido'],
            'email' => strtolower(trim($datosLimpios['email'])),
            'clave' => password_hash($datosLimpios['clave'], PASSWORD_BCRYPT),
            'estado' => 'activo',
            'rol' => 'user'
        ];

        $id = $this->model->crear($datosMapeados);

        return ['id' => $id];
    }

    /**
     * 2. Login - Verifica credenciales y genera JWT
     * CRÍTICO: Devuelve array limpio con campo 'rol'
     * Salida: { "usuario": {..., "rol": "beta"}, "token": "..." }
     */
    public function login(string $email, string $clave): array
    {
        // Buscar usuario
        $usuario = $this->model->buscarPorEmail($email);

        if (!$usuario) {
            throw new Exception('Credenciales incorrectas.', 401);
        }

        // Verificar contraseña
        if (!password_verify($clave, $usuario['clave_ahjr'])) {
            throw new Exception('Credenciales incorrectas.', 401);
        }

        // Verificar estado
        if ($usuario['estado_ahjr'] !== 'activo') {
            throw new Exception('Usuario inactivo.', 403);
        }

        // Limpiar sufijos _ahjr
        $usuarioLimpio = $this->limpiarSufijos($usuario);

        // Generar JWT
        $token = $this->generarJWT($usuarioLimpio);

        // Configurar Cookie Segura (Dual Auth)
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        setcookie(
            'sm_session',
            $token,
            [
                'expires' => time() + $this->jwtExpiration,
                'path' => '/',
                'domain' => '', // Current domain
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

        return [
            'usuario' => $usuarioLimpio,
            'token' => $token
        ];
    }

    /**
     * 3. Valida token JWT
     * Salida: payload del token
     */
    public function validarToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            return (array) $decoded;
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw new Exception('Token expirado.', 401);
        } catch (\Exception $e) {
            throw new Exception('Token inválido.', 401);
        }
    }

    // ===== MÉTODOS PRIVADOS (NO CUENTAN PARA RESTRICCIÓN) =====

    /**
     * Genera JWT con información del usuario
     */
    private function generarJWT(array $usuario): string
    {
        $payload = [
            'iss' => 'SubMate',
            'sub' => $usuario['id'],
            'iat' => time(),
            'exp' => time() + $this->jwtExpiration,
            'email' => $usuario['email'],
            'rol' => $usuario['rol']  // CRÍTICO: rol incluido
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    /**
     * Limpia sufijos _ahjr del array de BD
     */
    private function limpiarSufijos(array $datos): array
    {
        return [
            'id' => (int) $datos['id_ahjr'],
            'nombre' => $datos['nombre_ahjr'],
            'apellido' => $datos['apellido_ahjr'],
            'email' => $datos['email_ahjr'],
            'fecha_registro' => $datos['fecha_registro_ahjr'],
            'estado' => $datos['estado_ahjr'],
            'rol' => $datos['rol_ahjr']  // CRÍTICO: rol incluido
        ];
    }
}
