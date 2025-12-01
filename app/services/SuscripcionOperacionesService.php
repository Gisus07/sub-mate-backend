<?php

declare(strict_types=1);

namespace App\services;

use App\models\SuscripcionModel;
use App\models\SuscripcionOperacionesModel;
use App\services\AlertsService;
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
    private AlertsService $alerts;

    public function __construct()
    {
        $this->model = new SuscripcionModel();
        $this->operaciones = new SuscripcionOperacionesModel();
        $this->alerts = new AlertsService();
    }

    /**
     * 1. Gestiona cambio de estado con validación
     * @return array|bool
     */
    public function gestionarEstado(int $id, string $estado, int $userId, ?string $frecuencia = null, ?float $costo = null)
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

        // Validar frecuencia si se proporciona
        if ($frecuencia !== null) {
            $frecuencia = strtolower($frecuencia);
            if (!in_array($frecuencia, ['mensual', 'anual'])) {
                throw new Exception('Frecuencia inválida. Valores permitidos: mensual, anual.', 400);
            }
        }

        // Calcular día y mes actuales para reactivación
        $diaCobro = null;
        $mesCobro = null;
        $costoFinal = null;

        if ($estado === 'activa') {
            $diaCobro = (int)date('d');
            $mesCobro = (int)date('m');

            // CRÍTICO: Si la frecuencia es MENSUAL, el mes_cobro DEBE ser NULL
            // Determinar frecuencia final
            $frecuenciaFinal = $frecuencia ?? $suscripcion['frecuencia_ahjr'];
            if (strtolower($frecuenciaFinal) === 'mensual') {
                $mesCobro = null;
            }

            // CRÍTICO: Actualizar costo SOLO si la frecuencia cambia
            $frecuenciaOriginal = strtolower($suscripcion['frecuencia_ahjr']);
            if (strtolower($frecuenciaFinal) !== $frecuenciaOriginal) {
                $costoFinal = $costo;
            }
        }

        $resultado = $this->operaciones->cambiarEstado($id, $estado, $userId, $frecuencia, $diaCobro, $mesCobro, $costoFinal);

        if ($resultado) {
            try {
                if ($estado === 'inactiva') {
                    $this->alerts->enviarSuscripcionDesactivada($suscripcion);
                } elseif ($estado === 'activa') {
                    $this->alerts->enviarSuscripcionActivada($suscripcion);
                }
            } catch (Exception $e) {
                error_log("Error enviando alerta de estado (Operaciones): " . $e->getMessage());
            }
        }

        return $resultado;
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
