<?php

declare(strict_types=1);

namespace App\services;

use App\models\UsuarioModel;
use Exception;

/**
 * UsuarioService - Lógica de Gestión de Perfil
 * 
 * RESTRICCIÓN: Máximo 5 métodos públicos
 * Responsabilidad: Gestión de cuenta (NO autenticación)
 */
class UsuarioService
{
    private UsuarioModel $model_AHJR;

    public function __construct()
    {
        $this->model_AHJR = new UsuarioModel();
    }

    /**
     * 1. Obtiene perfil de usuario
     * Salida: array limpio sin sufijos _ahjr
     */
    public function obtenerPerfil_AHJR(int $id_AHJR): array
    {
        $usuario_AHJR = $this->model_AHJR->obtenerPorId_AHJR($id_AHJR);

        if (!$usuario_AHJR) {
            throw new Exception('Usuario no encontrado.', 404);
        }

        // Limpiar sufijos _ahjr antes de devolver
        return $this->limpiarSufijos_AHJR($usuario_AHJR);
    }

    /**
     * 2. Edita perfil de usuario
     * Entrada: datos limpios { "nombre": "Juan", ... }
     */
    public function editarPerfil_AHJR(int $id_AHJR, array $datosLimpios_AHJR): bool
    {
        if (!$this->model_AHJR->obtenerPorId_AHJR($id_AHJR)) {
            throw new Exception('Usuario no encontrado.', 404);
        }

        // Validar email único si se está cambiando
        if (isset($datosLimpios_AHJR['email'])) {
            $existente_AHJR = $this->model_AHJR->buscarPorEmail_AHJR($datosLimpios_AHJR['email']);
            if ($existente_AHJR && (int)$existente_AHJR['id_ahjr'] !== $id_AHJR) {
                throw new Exception('Este correo ya está en uso.', 409);
            }
        }

        // Mapear a formato DB (agregar sufijos _ahjr)
        $datosMapeados_AHJR = [];
        if (isset($datosLimpios_AHJR['nombre'])) {
            $datosMapeados_AHJR['nombre_ahjr'] = $datosLimpios_AHJR['nombre'];
        }
        if (isset($datosLimpios_AHJR['apellido'])) {
            $datosMapeados_AHJR['apellido_ahjr'] = $datosLimpios_AHJR['apellido'];
        }
        if (isset($datosLimpios_AHJR['email'])) {
            $datosMapeados_AHJR['email_ahjr'] = strtolower(trim($datosLimpios_AHJR['email']));
        }

        return $this->model_AHJR->actualizar_AHJR($id_AHJR, $datosMapeados_AHJR);
    }

    /**
     * 3. Cambia contraseña del usuario
     */
    public function cambiarClave_AHJR(int $id_AHJR, string $claveActual_AHJR, string $claveNueva_AHJR): bool
    {
        $usuario_AHJR = $this->model_AHJR->obtenerPorId_AHJR($id_AHJR);

        if (!$usuario_AHJR) {
            throw new Exception('Usuario no encontrado.', 404);
        }

        // Verificar que la contraseña actual sea correcta
        if (!password_verify($claveActual_AHJR, $usuario_AHJR['clave_ahjr'])) {
            throw new Exception('La contraseña actual es incorrecta.', 401);
        }

        // Hash de la nueva contraseña
        $hash_AHJR = password_hash($claveNueva_AHJR, PASSWORD_BCRYPT);

        return $this->model_AHJR->actualizar_AHJR($id_AHJR, ['clave_ahjr' => $hash_AHJR]);
    }

    // ===== MÉTODOS PRIVADOS =====

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
            'rol' => $datos_AHJR['rol_ahjr']
        ];
    }
}
