<?php

declare(strict_types=1);

namespace App\services;

use App\models\UsuarioModel;
use App\models\UsuarioOTPModel;
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
    private UsuarioModel $model_AHJR;
    private UsuarioOTPModel $otpModel_AHJR;
    private string $jwtSecret_AHJR;
    private int $jwtExpiration_AHJR = 86400; // 24 horas

    public function __construct()
    {
        $this->model_AHJR = new UsuarioModel();
        $this->otpModel_AHJR = new UsuarioOTPModel();
        $this->jwtSecret_AHJR = $_ENV['JWT_SECRET'] ?? 'default_secret_change_me';
    }

    /**
     * 1. Registra nuevo usuario
     * Entrada: datos limpios { "nombre": "Juan", "email": "..." }
     * Salida: { "id": 1 }
     */
    public function registrarUsuario_AHJR(array $datosLimpios_AHJR): array
    {
        // 1. Validar si ya existe como usuario activo
        if ($this->model_AHJR->buscarPorEmail_AHJR($datosLimpios_AHJR['email'])) {
            throw new Exception('Este correo ya está registrado.', 409);
        }

        // 2. Generar OTP
        $otp_AHJR = (string) random_int(100000, 999999);
        $otpHash_AHJR = password_hash($otp_AHJR, PASSWORD_BCRYPT);
        $expira_AHJR = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // 3. Guardar en tabla temporal (Pendientes)
        $datosPendientes_AHJR = [
            'nombre' => $datosLimpios_AHJR['nombre'],
            'apellido' => $datosLimpios_AHJR['apellido'],
            'email' => strtolower(trim($datosLimpios_AHJR['email'])),
            'clave' => password_hash($datosLimpios_AHJR['clave'], PASSWORD_BCRYPT),
            'otp_hash' => $otpHash_AHJR,
            'otp_expira' => $expira_AHJR
        ];

        $this->otpModel_AHJR->crearRegistroPendiente($datosPendientes_AHJR);

        // 4. Enviar Email
        \App\core\Mailer::sendOTP_ahjr($datosPendientes_AHJR['email'], $otp_AHJR);

        return ['message' => 'Código de verificación enviado al correo.'];
    }

    /**
     * 2. Verifica OTP y activa usuario
     */
    public function verificarYActivar_AHJR(string $email_AHJR, string $otp_AHJR): array
    {
        $pendiente_AHJR = $this->otpModel_AHJR->obtenerPendientePorEmail($email_AHJR);

        if (!$pendiente_AHJR) {
            throw new Exception('No hay registro pendiente para este email.', 404);
        }

        if (!password_verify($otp_AHJR, $pendiente_AHJR['otp_hash_ahjr'])) {
            throw new Exception('Código incorrecto.', 400);
        }

        if (strtotime($pendiente_AHJR['otp_expira_ahjr']) < time()) {
            throw new Exception('El código ha expirado.', 400);
        }

        // Mover a usuarios activos
        $datosUsuario_AHJR = [
            'nombre' => $pendiente_AHJR['nombre_ahjr'],
            'apellido' => $pendiente_AHJR['apellido_ahjr'],
            'email' => $pendiente_AHJR['email_ahjr'],
            'clave' => $pendiente_AHJR['clave_ahjr'], // Ya está hasheada
            'estado' => 'activo',
            'rol' => 'user'
        ];

        $id_AHJR = $this->model_AHJR->crear_AHJR($datosUsuario_AHJR);

        // Eliminar pendiente
        $this->otpModel_AHJR->eliminarPendiente($pendiente_AHJR['id_pendiente_ahjr']);

        return ['id' => $id_AHJR, 'message' => 'Cuenta verificada y creada exitosamente.'];
    }

    /**
     * 3. Solicitar Reset de Contraseña
     */
    public function solicitarResetPassword_AHJR(string $email_AHJR): array
    {
        // Verificar si el usuario existe
        if (!$this->model_AHJR->buscarPorEmail_AHJR($email_AHJR)) {
            // Por seguridad, retornamos éxito genérico
            return ['message' => 'Si el correo existe, se ha enviado un código de recuperación.'];
        }

        // Generar OTP
        $otp_AHJR = (string) random_int(100000, 999999);
        $otpHash_AHJR = password_hash($otp_AHJR, PASSWORD_BCRYPT);
        $expira_AHJR = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $datosReset_AHJR = [
            'email' => strtolower(trim($email_AHJR)),
            'otp_hash' => $otpHash_AHJR,
            'otp_expira' => $expira_AHJR
        ];

        $this->otpModel_AHJR->crearResetPendiente($datosReset_AHJR);

        // Enviar Email
        \App\core\Mailer::sendOTP_ahjr($email_AHJR, $otp_AHJR);

        return ['message' => 'Si el correo existe, se ha enviado un código de recuperación.'];
    }

    /**
     * 4. Verificar Reset y Cambiar Contraseña
     */
    public function verificarResetPassword_AHJR(string $email_AHJR, string $otp_AHJR, string $nuevaClave_AHJR): array
    {
        $reset_AHJR = $this->otpModel_AHJR->obtenerResetPorEmail($email_AHJR);

        if (!$reset_AHJR) {
            throw new Exception('Solicitud no encontrada o expirada.', 404);
        }

        if (!password_verify($otp_AHJR, $reset_AHJR['otp_hash_ahjr'])) {
            throw new Exception('Código incorrecto.', 400);
        }

        if (strtotime($reset_AHJR['otp_expira_ahjr']) < time()) {
            throw new Exception('El código ha expirado.', 400);
        }

        // Buscar usuario para obtener ID
        $usuario_AHJR = $this->model_AHJR->buscarPorEmail_AHJR($email_AHJR);
        if (!$usuario_AHJR) {
            throw new Exception('Usuario no encontrado.', 404);
        }

        // Actualizar contraseña
        $nuevaClaveHash_AHJR = password_hash($nuevaClave_AHJR, PASSWORD_BCRYPT);
        $this->model_AHJR->actualizar_AHJR($usuario_AHJR['id_ahjr'], ['clave_ahjr' => $nuevaClaveHash_AHJR]);

        // Eliminar solicitud de reset
        $this->otpModel_AHJR->eliminarReset($reset_AHJR['id_reset_ahjr']);

        return ['message' => 'Contraseña actualizada correctamente.'];
    }

    /**
     * 2. Login - Verifica credenciales y genera JWT
     * CRÍTICO: Devuelve array limpio con campo 'rol'
     * Salida: { "usuario": {..., "rol": "beta"}, "token": "..." }
     */
    public function login_AHJR(string $email_AHJR, string $clave_AHJR): array
    {
        // Buscar usuario
        $usuario_AHJR = $this->model_AHJR->buscarPorEmail_AHJR($email_AHJR);

        if (!$usuario_AHJR) {
            throw new Exception('Credenciales incorrectas.', 401);
        }

        // Verificar contraseña
        if (!password_verify($clave_AHJR, $usuario_AHJR['clave_ahjr'])) {
            throw new Exception('Credenciales incorrectas.', 401);
        }

        // Verificar estado
        if ($usuario_AHJR['estado_ahjr'] !== 'activo') {
            throw new Exception('Usuario inactivo.', 403);
        }

        // Limpiar sufijos _ahjr
        $usuarioLimpio_AHJR = $this->limpiarSufijos_AHJR($usuario_AHJR);

        // Generar JWT
        $token_AHJR = $this->generarJWT_AHJR($usuarioLimpio_AHJR);

        // Configurar Cookie Segura (Dual Auth)
        $secure_AHJR = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        setcookie(
            'sm_session',
            $token_AHJR,
            [
                'expires' => time() + $this->jwtExpiration_AHJR,
                'path' => '/',
                'domain' => '', // Current domain
                'secure' => $secure_AHJR,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

        return [
            'usuario' => $usuarioLimpio_AHJR,
            'token' => $token_AHJR
        ];
    }

    /**
     * 3. Valida token JWT
     * Salida: payload del token
     */
    public function validarToken_AHJR(string $token_AHJR): array
    {
        try {
            $decoded_AHJR = JWT::decode($token_AHJR, new Key($this->jwtSecret_AHJR, 'HS256'));
            return (array) $decoded_AHJR;
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
    private function generarJWT_AHJR(array $usuario_AHJR): string
    {
        $payload_AHJR = [
            'iss' => 'SubMate',
            'sub' => $usuario_AHJR['id'],
            'iat' => time(),
            'exp' => time() + $this->jwtExpiration_AHJR,
            'email' => $usuario_AHJR['email'],
            'rol' => $usuario_AHJR['rol']  // CRÍTICO: rol incluido
        ];

        return JWT::encode($payload_AHJR, $this->jwtSecret_AHJR, 'HS256');
    }

    /**
     * Limpia sufijos _ahjr del array de BD
     */
    private function limpiarSufijos_AHJR(array $datos_AHJR): array
    {
        return [
            'id' => (int) $datos_AHJR['id_ahjr'],
            'nombre' => $datos_AHJR['nombre_ahjr'],
            'apellido' => $datos_AHJR['apellido_ahjr'],
            'email' => $datos_AHJR['email_ahjr'],
            'fecha_registro' => $datos_AHJR['fecha_registro_ahjr'],
            'estado' => $datos_AHJR['estado_ahjr'],
            'rol' => $datos_AHJR['rol_ahjr']  // CRÍTICO: rol incluido
        ];
    }
}
