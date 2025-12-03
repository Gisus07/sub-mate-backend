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
    private SuscripcionModel $model_AHJR;
    private SuscripcionOperacionesModel $operaciones_AHJR;
    private AlertsService $alerts_AHJR;

    public function __construct()
    {
        $this->model_AHJR = new SuscripcionModel();
        $this->operaciones_AHJR = new SuscripcionOperacionesModel();
        $this->alerts_AHJR = new AlertsService();
    }

    /**
     * 1. Gestiona cambio de estado con validación
     * @return array|bool
     */
    public function gestionarEstado_AHJR(int $id_AHJR, string $estado_AHJR, int $userId_AHJR, ?string $frecuencia_AHJR = null, ?float $costo_AHJR = null)
    {
        // Verificar propiedad
        $suscripcion_AHJR = $this->model_AHJR->obtener_AHJR($id_AHJR, $userId_AHJR);
        if (!$suscripcion_AHJR) {
            throw new Exception('Suscripción no encontrada.', 404);
        }

        // Validar estado
        if (!in_array($estado_AHJR, ['activa', 'inactiva'])) {
            throw new Exception('Estado inválido.', 400);
        }

        // Validar frecuencia si se proporciona
        if ($frecuencia_AHJR !== null) {
            $frecuencia_AHJR = strtolower($frecuencia_AHJR);
            if (!in_array($frecuencia_AHJR, ['mensual', 'anual'])) {
                throw new Exception('Frecuencia inválida. Valores permitidos: mensual, anual.', 400);
            }
        }

        // Calcular día y mes actuales para reactivación
        $diaCobro_AHJR = null;
        $mesCobro_AHJR = null;
        $costoFinal_AHJR = null;

        if ($estado_AHJR === 'activa') {
            $diaCobro_AHJR = (int)date('d');
            $mesCobro_AHJR = (int)date('m');

            // CRÍTICO: Si la frecuencia es MENSUAL, el mes_cobro DEBE ser NULL
            // Determinar frecuencia final
            $frecuenciaFinal_AHJR = $frecuencia_AHJR ?? $suscripcion_AHJR['frecuencia_ahjr'];
            if (strtolower($frecuenciaFinal_AHJR) === 'mensual') {
                $mesCobro_AHJR = null;
            }

            // CRÍTICO: Actualizar costo SOLO si la frecuencia cambia
            $frecuenciaOriginal_AHJR = strtolower($suscripcion_AHJR['frecuencia_ahjr']);
            if (strtolower($frecuenciaFinal_AHJR) !== $frecuenciaOriginal_AHJR) {
                $costoFinal_AHJR = $costo_AHJR;
            }
        }

        $resultado_AHJR = $this->operaciones_AHJR->cambiarEstado_AHJR($id_AHJR, $estado_AHJR, $userId_AHJR, $frecuencia_AHJR, $diaCobro_AHJR, $mesCobro_AHJR, $costoFinal_AHJR);

        if ($resultado_AHJR) {
            try {
                if ($estado_AHJR === 'inactiva') {
                    $this->alerts_AHJR->enviarSuscripcionDesactivada_AHJR($suscripcion_AHJR);
                } elseif ($estado_AHJR === 'activa') {
                    $this->alerts_AHJR->enviarSuscripcionActivada_AHJR($suscripcion_AHJR);
                }
            } catch (Exception $e) {
                error_log("Error enviando alerta de estado (Operaciones): " . $e->getMessage());
            }
        }

        return $resultado_AHJR;
    }

    /**
     * 2. Procesa simulación de pago
     */
    public function procesarSimulacionPago_AHJR(int $id_AHJR, string $metodo_AHJR, int $userId_AHJR): bool
    {
        // Verificar propiedad y obtener datos
        $suscripcion_AHJR = $this->model_AHJR->obtener_AHJR($id_AHJR, $userId_AHJR);
        if (!$suscripcion_AHJR) {
            throw new Exception('Suscripción no encontrada.', 404);
        }

        return $this->operaciones_AHJR->registrarPagoSimulado_AHJR(
            $id_AHJR,
            (float) $suscripcion_AHJR['costo_ahjr'],
            $metodo_AHJR,
            $suscripcion_AHJR['frecuencia_ahjr']
        );
    }
}
