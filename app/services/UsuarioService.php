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
    private UsuarioModel $model;

    public function __construct()
    {
        $this->model = new UsuarioModel();
    }

    /**
     * 1. Obtiene perfil de usuario
     * Salida: array limpio sin sufijos _ahjr
     */
    public function obtenerPerfil(int $id): array
    {
        $usuario = $this->model->obtenerPorId($id);

        if (!$usuario) {
            throw new Exception('Usuario no encontrado.', 404);
        }

        // Limpiar sufijos _ahjr antes de devolver
        return $this->limpiarSufijos($usuario);
    }

    /**
     * 2. Edita perfil de usuario
     * Entrada: datos limpios { "nombre": "Juan", ... }
     */
    public function editarPerfil(int $id, array $datosLimpios): bool
    {
        if (!$this->model->obtenerPorId($id)) {
            throw new Exception('Usuario no encontrado.', 404);
        }

        // Validar email único si se está cambiando
        if (isset($datosLimpios['email'])) {
            $existente = $this->model->buscarPorEmail($datosLimpios['email']);
            if ($existente && (int)$existente['id_ahjr'] !== $id) {
                throw new Exception('Este correo ya está en uso.', 409);
            }
        }

        // Mapear a formato DB (agregar sufijos _ahjr)
        $datosMapeados = [];
        if (isset($datosLimpios['nombre'])) {
            $datosMapeados['nombre_ahjr'] = $datosLimpios['nombre'];
        }
        if (isset($datosLimpios['apellido'])) {
            $datosMapeados['apellido_ahjr'] = $datosLimpios['apellido'];
        }
        if (isset($datosLimpios['email'])) {
            $datosMapeados['email_ahjr'] = strtolower(trim($datosLimpios['email']));
        }

        return $this->model->actualizar($id, $datosMapeados);
    }

    /**
     * 3. Cambia contraseña del usuario
     */
    public function cambiarClave(int $id, string $nuevaClave): bool
    {
        if (!$this->model->obtenerPorId($id)) {
            throw new Exception('Usuario no encontrado.', 404);
        }

        $hash = password_hash($nuevaClave, PASSWORD_BCRYPT);

        return $this->model->actualizar($id, ['clave_ahjr' => $hash]);
    }

    // ===== MÉTODOS PRIVADOS =====

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
            'rol' => $datos['rol_ahjr']
        ];
    }
}
