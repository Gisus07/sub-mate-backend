<?php

declare(strict_types=1);

namespace App\services;

use App\models\SuscripcionModel;
use App\models\SuscripcionOperacionesModel;
use App\services\AlertsService;
use Exception;

/**
 * SuscripcionService - Gestión Estándar
 * 
 * RESTRICCIÓN: Exactamente 6 métodos públicos
 */
class SuscripcionService
{
    private SuscripcionModel $model_AHJR;
    private SuscripcionOperacionesModel $operacionesModel_AHJR;
    private AlertsService $alerts_AHJR;

    public function __construct()
    {
        $this->model_AHJR = new SuscripcionModel();
        $this->operacionesModel_AHJR = new SuscripcionOperacionesModel();
        $this->alerts_AHJR = new AlertsService();
    }

    /**
     * 1. Crea suscripción (mapper + validación)
     */
    public function crear_AHJR(array $datosLimpios_AHJR, int $userId_AHJR): array
    {
        // Validar campos requeridos
        $requeridos_AHJR = ['nombre_servicio', 'costo', 'frecuencia', 'metodo_pago', 'dia_cobro'];
        foreach ($requeridos_AHJR as $campo_AHJR) {
            if (!isset($datosLimpios_AHJR[$campo_AHJR])) {
                throw new Exception("Campo {$campo_AHJR} requerido.", 400);
            }
        }

        // Verificar duplicados (Regla de Negocio)
        $nombreNormalizado_AHJR = strtolower(str_replace(' ', '', $datosLimpios_AHJR['nombre_servicio']));
        if ($this->model_AHJR->buscarSuscripcionPorNombre_AHJR($userId_AHJR, $nombreNormalizado_AHJR)) {
            throw new Exception("Ya tienes una suscripción registrada con ese nombre.", 409);
        }

        // Preparar para SP (NO usa sufijos _ahjr, el SP los maneja internamente)
        $mesCobro_AHJR = $datosLimpios_AHJR['mes_cobro'] ?? null;
        if (strtolower($datosLimpios_AHJR['frecuencia']) === 'mensual') {
            $mesCobro_AHJR = null;
        }

        $datos_AHJR = [
            'id_usuario' => $userId_AHJR,
            'nombre_servicio' => $datosLimpios_AHJR['nombre_servicio'],
            'costo' => (float) $datosLimpios_AHJR['costo'],
            'frecuencia' => $datosLimpios_AHJR['frecuencia'],
            'metodo_pago' => $datosLimpios_AHJR['metodo_pago'],
            'dia_cobro' => (int) $datosLimpios_AHJR['dia_cobro'],
            'mes_cobro' => $mesCobro_AHJR
        ];

        $id_AHJR = $this->model_AHJR->crear_AHJR($datos_AHJR);

        // --- LÓGICA DE PAGO HISTÓRICO ---
        // Recuperar la suscripción recién creada para ver qué calculó el SP
        $suscripcionCreada_AHJR = $this->model_AHJR->obtener_AHJR($id_AHJR, $userId_AHJR);

        if ($suscripcionCreada_AHJR && !empty($suscripcionCreada_AHJR['fecha_ultimo_pago_ahjr'])) {
            $fechaUltimoPago_AHJR = $suscripcionCreada_AHJR['fecha_ultimo_pago_ahjr'];
            $hoy_AHJR = date('Y-m-d');

            // Si la fecha de último pago es hoy o anterior, registrar en historial
            if ($fechaUltimoPago_AHJR <= $hoy_AHJR) {
                $this->operacionesModel_AHJR->registrarPagoHistoricoManual_AHJR(
                    $id_AHJR,
                    (float) $datos_AHJR['costo'],
                    $fechaUltimoPago_AHJR,
                    $datos_AHJR['metodo_pago']
                );
            }
        }
        // --------------------------------

        // Notificar creación
        try {
            $this->alerts_AHJR->enviarSuscripcionCreada_AHJR($datos_AHJR);
        } catch (Exception $e) {
            // No bloquear el flujo si falla el correo
            error_log("Error enviando alerta de creación: " . $e->getMessage());
        }

        return ['id' => $id_AHJR];
    }

    /**
     * 2. Obtiene lista de suscripciones (mapper de salida)
     */
    public function obtenerLista_AHJR(int $userId_AHJR): array
    {
        $suscripciones_AHJR = $this->model_AHJR->listarPorUsuario_AHJR($userId_AHJR);
        return array_map([$this, 'limpiarSufijos_AHJR'], $suscripciones_AHJR);
    }

    /**
     * 3. Obtiene detalle de suscripción
     */
    public function obtenerDetalle_AHJR(int $id_AHJR, int $userId_AHJR): array
    {
        $suscripcion_AHJR = $this->model_AHJR->obtener_AHJR($id_AHJR, $userId_AHJR);

        if (!$suscripcion_AHJR) {
            throw new Exception('Suscripción no encontrada.', 404);
        }

        return $this->limpiarSufijos_AHJR($suscripcion_AHJR);
    }

    /**
     * 4. Modifica suscripción
     */
    public function modificar_AHJR(int $id_AHJR, array $datosLimpios_AHJR, int $userId_AHJR): bool
    {
        // Verificar propiedad
        $suscripcion_AHJR = $this->model_AHJR->obtener_AHJR($id_AHJR, $userId_AHJR);
        if (!$suscripcion_AHJR) {
            throw new Exception('Suscripción no encontrada.', 404);
        }

        // BLOQUEO CRÍTICO: No permitir cambio de nombre
        if (isset($datosLimpios_AHJR['nombre_servicio'])) {
            $nombreActual_AHJR = strtolower(str_replace(' ', '', $suscripcion_AHJR['nombre_servicio_ahjr']));
            $nombreNuevo_AHJR = strtolower(str_replace(' ', '', $datosLimpios_AHJR['nombre_servicio']));

            if ($nombreActual_AHJR !== $nombreNuevo_AHJR) {
                throw new Exception("No se permite cambiar el nombre del servicio.", 400);
            }
        }

        // Mapear a formato DB
        $datosMapeados_AHJR = [];
        // NOTA: nombre_servicio NO se agrega a $datosMapeados para asegurar que no cambie

        if (isset($datosLimpios_AHJR['costo'])) {
            $datosMapeados_AHJR['costo_ahjr'] = (float) $datosLimpios_AHJR['costo'];
        }
        if (isset($datosLimpios_AHJR['metodo_pago'])) {
            $datosMapeados_AHJR['metodo_pago_ahjr'] = $datosLimpios_AHJR['metodo_pago'];
        }
        if (isset($datosLimpios_AHJR['dia_cobro'])) {
            $datosMapeados_AHJR['dia_cobro_ahjr'] = (int) $datosLimpios_AHJR['dia_cobro'];
        }

        // Lógica para mes_cobro y frecuencia
        $frecuenciaOriginal_AHJR = strtolower($suscripcion_AHJR['frecuencia_ahjr']);
        $nuevaFrecuencia_AHJR = isset($datosLimpios_AHJR['frecuencia']) ? strtolower($datosLimpios_AHJR['frecuencia']) : $frecuenciaOriginal_AHJR;

        if (isset($datosLimpios_AHJR['frecuencia'])) {
            $datosMapeados_AHJR['frecuencia_ahjr'] = $datosLimpios_AHJR['frecuencia'];
        }

        if ($nuevaFrecuencia_AHJR === 'mensual') {
            $datosMapeados_AHJR['mes_cobro_ahjr'] = null;
        } elseif (isset($datosLimpios_AHJR['mes_cobro'])) {
            $datosMapeados_AHJR['mes_cobro_ahjr'] = (int) $datosLimpios_AHJR['mes_cobro'];
        }

        // RECÁLCULO CONDICIONAL: Solo si cambia la frecuencia Y está activa
        if ($nuevaFrecuencia_AHJR !== $frecuenciaOriginal_AHJR && strtolower($suscripcion_AHJR['estado_ahjr']) === 'activa') {
            // Usar el día de cobro nuevo o el existente
            $diaCobroCalc_AHJR = isset($datosMapeados_AHJR['dia_cobro_ahjr']) ? $datosMapeados_AHJR['dia_cobro_ahjr'] : (int)$suscripcion_AHJR['dia_cobro_ahjr'];

            // Recalcular fecha próximo pago
            $datosMapeados_AHJR['fecha_proximo_pago_ahjr'] = $this->calcularProximoPago_AHJR($nuevaFrecuencia_AHJR, $diaCobroCalc_AHJR);
        }
        // Si la frecuencia NO cambia, NO se tocan las fechas (ni proximo ni ultimo pago)

        if (empty($datosMapeados_AHJR)) {
            return true; // Nada que actualizar
        }

        $resultado_AHJR = $this->model_AHJR->editar_AHJR($id_AHJR, $datosMapeados_AHJR);

        if ($resultado_AHJR) {
            // Notificar edición
            try {
                // Fusionar datos para el correo
                $datosCompletos_AHJR = array_merge($suscripcion_AHJR, $datosMapeados_AHJR);
                // Asegurar que nombre_servicio esté disponible (ya que no cambia)
                $datosCompletos_AHJR['nombre_servicio'] = $suscripcion_AHJR['nombre_servicio_ahjr'];
                $this->alerts_AHJR->enviarSuscripcionEditada_AHJR($datosCompletos_AHJR);
            } catch (Exception $e) {
                error_log("Error enviando alerta de edición: " . $e->getMessage());
            }
        }

        return $resultado_AHJR;
    }

    /**
     * 5. Elimina suscripción
     */
    public function borrar_AHJR(int $id_AHJR, int $userId_AHJR): bool
    {
        // Verificar propiedad
        $suscripcion_AHJR = $this->model_AHJR->obtener_AHJR($id_AHJR, $userId_AHJR);
        if (!$suscripcion_AHJR) {
            throw new Exception('Suscripción no encontrada.', 404);
        }

        $resultado_AHJR = $this->model_AHJR->eliminar_AHJR($id_AHJR);

        if ($resultado_AHJR) {
            // Notificar eliminación
            try {
                $this->alerts_AHJR->enviarSuscripcionEliminada_AHJR($suscripcion_AHJR);
            } catch (Exception $e) {
                error_log("Error enviando alerta de eliminación: " . $e->getMessage());
            }
        }

        return $resultado_AHJR;
    }

    /**
     * 6. Cambia el estado de la suscripción (Activar/Desactivar)
     */
    public function cambiarEstado_AHJR(int $id_AHJR, int $userId_AHJR, string $nuevoEstado_AHJR, ?int $diaCobro_AHJR = null): array
    {
        // 1. Verificar propiedad y obtener estado actual
        $suscripcion_AHJR = $this->model_AHJR->obtener_AHJR($id_AHJR, $userId_AHJR);
        if (!$suscripcion_AHJR) {
            throw new Exception('Suscripción no encontrada.', 404);
        }

        $datosActualizar_AHJR = [];
        $nuevoEstadoInput_AHJR = strtoupper($nuevoEstado_AHJR); // Normalizar entrada
        $estadoActual_AHJR = $suscripcion_AHJR['estado_ahjr']; // 'activa' o 'inactiva'

        // Mapeo de entrada a valor BD
        $estadoDB_AHJR = ($nuevoEstadoInput_AHJR === 'ACTIVO') ? 'activa' : 'inactiva';

        // Guardrail: Si el estado es el mismo, no hacer nada
        if ($estadoDB_AHJR === $estadoActual_AHJR) {
            return $this->limpiarSufijos_AHJR($suscripcion_AHJR);
        }

        // 2. Lógica según estado
        if ($nuevoEstadoInput_AHJR === 'INACTIVO') {
            $datosActualizar_AHJR['estado_ahjr'] = 'inactiva';
            $datosActualizar_AHJR['fecha_proximo_pago_ahjr'] = null;
        } elseif ($nuevoEstadoInput_AHJR === 'ACTIVO') {
            $datosActualizar_AHJR['estado_ahjr'] = 'activa';
            $datosActualizar_AHJR['fecha_ultimo_pago_ahjr'] = date('Y-m-d'); // Fecha actual

            // Si se proporciona día de cobro, actualizarlo
            if ($diaCobro_AHJR !== null) {
                $datosActualizar_AHJR['dia_cobro_ahjr'] = $diaCobro_AHJR;
            }

            // Calcular próximo pago
            $diaCobroCalc_AHJR = $diaCobro_AHJR ?? (int)$suscripcion_AHJR['dia_cobro_ahjr'];
            $frecuencia_AHJR = $suscripcion_AHJR['frecuencia_ahjr'];

            $datosActualizar_AHJR['fecha_proximo_pago_ahjr'] = $this->calcularProximoPago_AHJR($frecuencia_AHJR, $diaCobroCalc_AHJR);
        } else {
            throw new Exception("Estado no válido: {$nuevoEstado_AHJR}", 400);
        }

        // 3. Guardar cambios
        if (!$this->model_AHJR->editar_AHJR($id_AHJR, $datosActualizar_AHJR)) {
            throw new Exception("No se pudo actualizar el estado.", 500);
        }

        // Notificar cambio de estado (solo si se desactiva, según requerimiento)
        try {
            if ($nuevoEstadoInput_AHJR === 'INACTIVO') {
                $this->alerts_AHJR->enviarSuscripcionDesactivada_AHJR($suscripcion_AHJR);
            }
        } catch (Exception $e) {
            error_log("Error enviando alerta de estado: " . $e->getMessage());
        }

        // 4. Retornar suscripción actualizada
        return $this->obtenerDetalle_AHJR($id_AHJR, $userId_AHJR);
    }

    // ===== MÉTODOS PRIVADOS =====

    private function calcularProximoPago_AHJR(string $frecuencia_AHJR, int $diaCobro_AHJR): string
    {
        $fecha_AHJR = new \DateTime(); // Hoy
        $frecuencia_AHJR = strtoupper($frecuencia_AHJR);

        if ($frecuencia_AHJR === 'MENSUAL') {
            $fecha_AHJR->modify('+1 month');
        } elseif ($frecuencia_AHJR === 'ANUAL') {
            $fecha_AHJR->modify('+1 year');
        } elseif ($frecuencia_AHJR === 'SEMANAL') {
            $fecha_AHJR->modify('+1 week');
            return $fecha_AHJR->format('Y-m-d');
        }

        // Ajustar el día para Mensual/Anual
        $year_AHJR = (int)$fecha_AHJR->format('Y');
        $month_AHJR = (int)$fecha_AHJR->format('m');

        // Validar si el día existe en ese mes (ej: 31 Feb)
        if (!checkdate($month_AHJR, $diaCobro_AHJR, $year_AHJR)) {
            // Si no existe, tomar el último día del mes
            $diaCobro_AHJR = (int)$fecha_AHJR->format('t');
        }

        $fecha_AHJR->setDate($year_AHJR, $month_AHJR, $diaCobro_AHJR);

        return $fecha_AHJR->format('Y-m-d');
    }

    private function limpiarSufijos_AHJR(array $datos_AHJR): array
    {
        return [
            'id' => (int) $datos_AHJR['id_suscripcion_ahjr'],
            'nombre_servicio' => $datos_AHJR['nombre_servicio_ahjr'],
            'costo' => (float) $datos_AHJR['costo_ahjr'],
            'estado' => $datos_AHJR['estado_ahjr'],
            'frecuencia' => $datos_AHJR['frecuencia_ahjr'],
            'metodo_pago' => $datos_AHJR['metodo_pago_ahjr'],
            'dia_cobro' => $datos_AHJR['dia_cobro_ahjr'] ? (int) $datos_AHJR['dia_cobro_ahjr'] : null,
            'mes_cobro' => $datos_AHJR['mes_cobro_ahjr'] ? (int) $datos_AHJR['mes_cobro_ahjr'] : null,
            'fecha_ultimo_pago' => $datos_AHJR['fecha_ultimo_pago_ahjr'],
            'fecha_proximo_pago' => $datos_AHJR['fecha_proximo_pago_ahjr'] ?? null,
            'dias_restantes' => isset($datos_AHJR['dias_restantes_ahjr']) ? (int) $datos_AHJR['dias_restantes_ahjr'] : null,
            'fecha_creacion' => $datos_AHJR['fecha_creacion_ahjr']
        ];
    }
}
