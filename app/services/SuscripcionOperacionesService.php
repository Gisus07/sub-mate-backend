<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SuscripcionModel;
use App\Models\SuscripcionOperacionesModel;
use Exception;

/**
 * SuscripcionOperacionesService - Gestión Operativa
 * 
 * RESTRICCIÓN: Máximo 5 métodos públicos (tiene 2)
 */
class SuscripcionOperacionesService
{
    private SuscripcionModel $model;
    private SuscripcionOperacionesModel $operaciones;

    public function __construct()
    {
        $this->model = new SuscripcionModel();
        $this->operaciones = new SuscripcionOperacionesModel();
    }

    /**
     * 1. Gestiona cambio de estado con validación
     */
    public function gestionarEstado(int $id, string $estado, int $userId): bool
    {
        // Verificar propiedad
        $suscripcion = $this->model->obtener($id, $userId);
        if (!$suscripcion) {
            throw new Exception('Suscripción no encontrada.', 404);
        }

        // Validar estado
        if (!in_array($estado, ['activa', 'inactiva'])) {
            throw new Exception('Estado inválido.', 400);
        }

        return $this->operaciones->cambiarEstado($id, $estado);
    }

    /**
     * 2. Procesa simulación de pago
     */
    public function procesarSimulacionPago(int $id, string $metodo, int $userId): bool
    {
        // Verificar propiedad y obtener datos
        $suscripcion = $this->model->obtener($id, $userId);
        if (!$suscripcion) {
            throw new Exception('Suscripción no encontrada.', 404);
        }

        return $this->operaciones->registrarPagoSimulado(
            $id,
            (float) $suscripcion['costo_ahjr'],
            $metodo,
            $suscripcion['frecuencia_ahjr']
        );
    }
}
