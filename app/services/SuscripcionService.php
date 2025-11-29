<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SuscripcionModel;
use Exception;

/**
 * SuscripcionService - Gestión Estándar
 * 
 * RESTRICCIÓN: Exactamente 5 métodos públicos
 */
class SuscripcionService
{
    private SuscripcionModel $model;

    public function __construct()
    {
        $this->model = new SuscripcionModel();
    }

    /**
     * 1. Crea suscripción (mapper + validación)
     */
    public function crear(array $datosLimpios, int $userId): array
    {
        // Validar campos requeridos
        $requeridos = ['nombre_servicio', 'costo', 'frecuencia', 'metodo_pago', 'dia_cobro'];
        foreach ($requeridos as $campo) {
            if (!isset($datosLimpios[$campo])) {
                throw new Exception("Campo {$campo} requerido.", 400);
            }
        }

        // Preparar para SP (NO usa sufijos _ahjr, el SP los maneja internamente)
        $datos = [
            'id_usuario' => $userId,
            'nombre_servicio' => $datosLimpios['nombre_servicio'],
            'costo' => (float) $datosLimpios['costo'],
            'frecuencia' => $datosLimpios['frecuencia'],
            'metodo_pago' => $datosLimpios['metodo_pago'],
            'dia_cobro' => (int) $datosLimpios['dia_cobro'],
            'mes_cobro' => $datosLimpios['mes_cobro'] ?? null
        ];

        $id = $this->model->crear($datos);
        return ['id' => $id];
    }

    /**
     * 2. Obtiene lista de suscripciones (mapper de salida)
     */
    public function obtenerLista(int $userId): array
    {
        $suscripciones = $this->model->listarPorUsuario($userId);
        return array_map([$this, 'limpiarSufijos'], $suscripciones);
    }

    /**
     * 3. Obtiene detalle de suscripción
     */
    public function obtenerDetalle(int $id, int $userId): array
    {
        $suscripcion = $this->model->obtener($id, $userId);

        if (!$suscripcion) {
            throw new Exception('Suscripción no encontrada.', 404);
        }

        return $this->limpiarSufijos($suscripcion);
    }

    /**
     * 4. Modifica suscripción
     */
    public function modificar(int $id, array $datosLimpios, int $userId): bool
    {
        // Verificar propiedad
        if (!$this->model->obtener($id, $userId)) {
            throw new Exception('Suscripción no encontrada.', 404);
        }

        // Mapear a formato DB
        $datosMapeados = [];
        if (isset($datosLimpios['nombre_servicio'])) {
            $datosMapeados['nombre_servicio_ahjr'] = $datosLimpios['nombre_servicio'];
        }
        if (isset($datosLimpios['costo'])) {
            $datosMapeados['costo_ahjr'] = (float) $datosLimpios['costo'];
        }
        if (isset($datosLimpios['metodo_pago'])) {
            $datosMapeados['metodo_pago_ahjr'] = $datosLimpios['metodo_pago'];
        }
        if (isset($datosLimpios['dia_cobro'])) {
            $datosMapeados['dia_cobro_ahjr'] = (int) $datosLimpios['dia_cobro'];
        }

        return $this->model->editar($id, $datosMapeados);
    }

    /**
     * 5. Elimina suscripción (borrar)
     */
    public function borrar(int $id, int $userId): bool
    {
        // Verificar propiedad
        if (!$this->model->obtener($id, $userId)) {
            throw new Exception('Suscripción no encontrada.', 404);
        }

        return $this->model->eliminar($id);
    }

    // ===== MÉTODOS PRIVADOS =====

    private function limpiarSufijos(array $datos): array
    {
        return [
            'id' => (int) $datos['id_suscripcion_ahjr'],
            'nombre_servicio' => $datos['nombre_servicio_ahjr'],
            'costo' => (float) $datos['costo_ahjr'],
            'estado' => $datos['estado_ahjr'],
            'frecuencia' => $datos['frecuencia_ahjr'],
            'metodo_pago' => $datos['metodo_pago_ahjr'],
            'dia_cobro' => (int) $datos['dia_cobro_ahjr'],
            'mes_cobro' => $datos['mes_cobro_ahjr'] ? (int) $datos['mes_cobro_ahjr'] : null,
            'fecha_ultimo_pago' => $datos['fecha_ultimo_pago_ahjr'],
            'fecha_creacion' => $datos['fecha_creacion_ahjr']
        ];
    }
}
