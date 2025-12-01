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
    private UsuarioModel $model;
    private UsuarioOTPModel $otpModel;
    private string $jwtSecret;
    private int $jwtExpiration = 86400; // 24 horas

    public function __construct()
    {
        $this->model = new UsuarioModel();
        $this->otpModel = new UsuarioOTPModel();
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'default_secret_change_me';
    }

    /**
     * 1. Registra nuevo usuario
     * Entrada: datos limpios { "nombre": "Juan", "email": "..." }
     * Salida: { "id": 1 }
     */
    public function registrarUsuario(array $datosLimpios): array
    {
        // 1. Validar si ya existe como usuario activo
        if ($this->model->buscarPorEmail($datosLimpios['email'])) {
            throw new Exception('Este correo ya está registrado.', 409);
        }

        // 2. Generar OTP
        $otp = (string) random_int(100000, 999999);
        $otpHash = password_hash($otp, PASSWORD_BCRYPT);
        $expira = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // 3. Guardar en tabla temporal (Pendientes)
        $datosPendientes = [
            'nombre' => $datosLimpios['nombre'],
            'apellido' => $datosLimpios['apellido'],
            'email' => strtolower(trim($datosLimpios['email'])),
            'clave' => password_hash($datosLimpios['clave'], PASSWORD_BCRYPT),
            'otp_hash' => $otpHash,
            'otp_expira' => $expira
        ];

        $this->otpModel->crearRegistroPendiente($datosPendientes);

        // 4. Enviar Email
        \App\Core\Mailer::sendOTP_ahjr($datosPendientes['email'], $otp);

        return ['message' => 'Código de verificación enviado al correo.'];
    }

    /**
     * 2. Verifica OTP y activa usuario
     */
    public function verificarYActivar(string $email, string $otp): array
    {
        $pendiente = $this->otpModel->obtenerPendientePorEmail($email);

        if (!$pendiente) {
            throw new Exception('No hay registro pendiente para este email.', 404);
        }

        if (!password_verify($otp, $pendiente['otp_hash_ahjr'])) {
            throw new Exception('Código incorrecto.', 400);
        }

        if (strtotime($pendiente['otp_expira_ahjr']) < time()) {
            throw new Exception('El código ha expirado.', 400);
        }

        // Mover a usuarios activos
        $datosUsuario = [
            'nombre' => $pendiente['nombre_ahjr'],
            'apellido' => $pendiente['apellido_ahjr'],
            'email' => $pendiente['email_ahjr'],
            'clave' => $pendiente['clave_ahjr'], // Ya está hasheada
            'estado' => 'activo',
            'rol' => 'user'
        ];

        // Insertar en tabla real (usando método existente que espera clave sin hash, pero aquí pasamos hash)
        // NOTA: UsuarioModel::crear espera clave plana para hashear? 
        // Revisando UsuarioModel::crear... NO, UsuarioModel::crear NO hashea, inserta directo.
        // AuthService::registrarUsuario original hasheaba ANTES de llamar a model->crear.
        // Entonces aquí pasamos la clave YA hasheada.

        // CORRECCIÓN: UsuarioModel::crear inserta lo que recibe. 
        // Pero AuthService::registrarUsuario original hacía: 'clave' => password_hash(...)
        // Mi nuevo registrarUsuario hace hash al guardar en pendiente.
        // Así que $pendiente['clave_ahjr'] YA es un hash.
        // Al llamar a $this->model->crear($datosUsuario), se insertará el hash.

        $id = $this->model->crear($datosUsuario);

        // Eliminar pendiente
        $this->otpModel->eliminarPendiente($pendiente['id_pendiente_ahjr']);

        return ['id' => $id, 'message' => 'Cuenta verificada y creada exitosamente.'];
    }

    /**
     * 3. Solicitar Reset de Contraseña
     */
    public function solicitarResetPassword(string $email): array
    {
        // Verificar si el usuario existe
        if (!$this->model->buscarPorEmail($email)) {
            // Por seguridad, no revelamos si el email existe o no, pero enviamos éxito simulado
            // Opcional: throw new Exception('Email no registrado', 404); si la política lo permite.
            // El usuario pidió "Solicitar reset", asumiremos que quiere saber si se envió.
            // Pero para evitar enumeración, retornamos éxito genérico.
            // Sin embargo, si no existe, no podemos enviar email real.
            // Retornaremos éxito siempre.
            return ['message' => 'Si el correo existe, se ha enviado un código de recuperación.'];
        }

        // Generar OTP
        $otp = (string) random_int(100000, 999999);
        $otpHash = password_hash($otp, PASSWORD_BCRYPT);
        $expira = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $datosReset = [
            'email' => strtolower(trim($email)),
            'otp_hash' => $otpHash,
            'otp_expira' => $expira
        ];

        $this->otpModel->crearResetPendiente($datosReset);

        // Enviar Email
        \App\Core\Mailer::sendOTP_ahjr($email, $otp); // Reusamos sendOTP o creamos uno específico?
        // El usuario dijo "siguiendo el mismo patrón de OTP". sendOTP sirve.

        return ['message' => 'Si el correo existe, se ha enviado un código de recuperación.'];
    }

    /**
     * 4. Verificar Reset y Cambiar Contraseña
     */
    public function verificarResetPassword(string $email, string $otp, string $nuevaClave): array
    {
        $reset = $this->otpModel->obtenerResetPorEmail($email);

        if (!$reset) {
            throw new Exception('Solicitud no encontrada o expirada.', 404);
        }

        if (!password_verify($otp, $reset['otp_hash_ahjr'])) {
            throw new Exception('Código incorrecto.', 400);
        }

        if (strtotime($reset['otp_expira_ahjr']) < time()) {
            throw new Exception('El código ha expirado.', 400);
        }

        // Buscar usuario para obtener ID
        $usuario = $this->model->buscarPorEmail($email);
        if (!$usuario) {
            throw new Exception('Usuario no encontrado.', 404);
        }

        // Actualizar contraseña
        $nuevaClaveHash = password_hash($nuevaClave, PASSWORD_BCRYPT);
        $this->model->actualizar($usuario['id_ahjr'], ['clave_ahjr' => $nuevaClaveHash]);

        // Eliminar solicitud de reset
        $this->otpModel->eliminarReset($reset['id_reset_ahjr']);

        return ['message' => 'Contraseña actualizada correctamente.'];
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
