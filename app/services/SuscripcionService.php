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
    private SuscripcionModel $model;
    private SuscripcionOperacionesModel $operacionesModel;
    private AlertsService $alerts;

    public function __construct()
    {
        $this->model = new SuscripcionModel();
        $this->operacionesModel = new SuscripcionOperacionesModel();
        $this->alerts = new AlertsService();
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

        // Verificar duplicados (Regla de Negocio)
        $nombreNormalizado = strtolower(str_replace(' ', '', $datosLimpios['nombre_servicio']));
        if ($this->model->buscarSuscripcionPorNombre($userId, $nombreNormalizado)) {
            throw new Exception("Ya tienes una suscripción registrada con ese nombre.", 409);
        }

        // Preparar para SP (NO usa sufijos _ahjr, el SP los maneja internamente)
        $mesCobro = $datosLimpios['mes_cobro'] ?? null;
        if (strtolower($datosLimpios['frecuencia']) === 'mensual') {
            $mesCobro = null;
        }

        $datos = [
            'id_usuario' => $userId,
            'nombre_servicio' => $datosLimpios['nombre_servicio'],
            'costo' => (float) $datosLimpios['costo'],
            'frecuencia' => $datosLimpios['frecuencia'],
            'metodo_pago' => $datosLimpios['metodo_pago'],
            'dia_cobro' => (int) $datosLimpios['dia_cobro'],
            'mes_cobro' => $mesCobro
        ];

        $id = $this->model->crear($datos);

        // --- LÓGICA DE PAGO HISTÓRICO ---
        // Recuperar la suscripción recién creada para ver qué calculó el SP
        $suscripcionCreada = $this->model->obtener($id, $userId);

        if ($suscripcionCreada && !empty($suscripcionCreada['fecha_ultimo_pago_ahjr'])) {
            $fechaUltimoPago = $suscripcionCreada['fecha_ultimo_pago_ahjr'];
            $hoy = date('Y-m-d');

            // Si la fecha de último pago es hoy o anterior, registrar en historial
            if ($fechaUltimoPago <= $hoy) {
                $this->operacionesModel->registrarPagoHistoricoManual(
                    $id,
                    (float) $datos['costo'],
                    $fechaUltimoPago,
                    $datos['metodo_pago']
                );
            }
        }
        // --------------------------------

        // Notificar creación
        try {
            $this->alerts->enviarSuscripcionCreada($datos);
        } catch (Exception $e) {
            // No bloquear el flujo si falla el correo
            error_log("Error enviando alerta de creación: " . $e->getMessage());
        }

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
        $suscripcion = $this->model->obtener($id, $userId);
        if (!$suscripcion) {
            throw new Exception('Suscripción no encontrada.', 404);
        }

        // BLOQUEO CRÍTICO: No permitir cambio de nombre
        if (isset($datosLimpios['nombre_servicio'])) {
            $nombreActual = strtolower(str_replace(' ', '', $suscripcion['nombre_servicio_ahjr']));
            $nombreNuevo = strtolower(str_replace(' ', '', $datosLimpios['nombre_servicio']));

            if ($nombreActual !== $nombreNuevo) {
                throw new Exception("No se permite cambiar el nombre del servicio.", 400);
            }
        }

        // Mapear a formato DB
        $datosMapeados = [];
        // NOTA: nombre_servicio NO se agrega a $datosMapeados para asegurar que no cambie

        if (isset($datosLimpios['costo'])) {
            $datosMapeados['costo_ahjr'] = (float) $datosLimpios['costo'];
        }
        if (isset($datosLimpios['metodo_pago'])) {
            $datosMapeados['metodo_pago_ahjr'] = $datosLimpios['metodo_pago'];
        }
        if (isset($datosLimpios['dia_cobro'])) {
            $datosMapeados['dia_cobro_ahjr'] = (int) $datosLimpios['dia_cobro'];
        }

        // Lógica para mes_cobro y frecuencia
        $frecuenciaOriginal = strtolower($suscripcion['frecuencia_ahjr']);
        $nuevaFrecuencia = isset($datosLimpios['frecuencia']) ? strtolower($datosLimpios['frecuencia']) : $frecuenciaOriginal;

        if (isset($datosLimpios['frecuencia'])) {
            $datosMapeados['frecuencia_ahjr'] = $datosLimpios['frecuencia'];
        }

        if ($nuevaFrecuencia === 'mensual') {
            $datosMapeados['mes_cobro_ahjr'] = null;
        } elseif (isset($datosLimpios['mes_cobro'])) {
            $datosMapeados['mes_cobro_ahjr'] = (int) $datosLimpios['mes_cobro'];
        }

        // RECÁLCULO CONDICIONAL: Solo si cambia la frecuencia Y está activa
        if ($nuevaFrecuencia !== $frecuenciaOriginal && strtolower($suscripcion['estado_ahjr']) === 'activa') {
            // Usar el día de cobro nuevo o el existente
            $diaCobroCalc = isset($datosMapeados['dia_cobro_ahjr']) ? $datosMapeados['dia_cobro_ahjr'] : (int)$suscripcion['dia_cobro_ahjr'];

            // Recalcular fecha próximo pago
            $datosMapeados['fecha_proximo_pago_ahjr'] = $this->calcularProximoPago($nuevaFrecuencia, $diaCobroCalc);
        }
        // Si la frecuencia NO cambia, NO se tocan las fechas (ni proximo ni ultimo pago)

        if (empty($datosMapeados)) {
            return true; // Nada que actualizar
        }

        $resultado = $this->model->editar($id, $datosMapeados);

        if ($resultado) {
            // Notificar edición
            try {
                // Fusionar datos para el correo
                $datosCompletos = array_merge($suscripcion, $datosMapeados);
                // Asegurar que nombre_servicio esté disponible (ya que no cambia)
                $datosCompletos['nombre_servicio'] = $suscripcion['nombre_servicio_ahjr'];
                $this->alerts->enviarSuscripcionEditada($datosCompletos);
            } catch (Exception $e) {
                error_log("Error enviando alerta de edición: " . $e->getMessage());
            }
        }

        return $resultado;
    }

    /**
     * 5. Elimina suscripción (borrar)
     */
    public function borrar(int $id, int $userId): bool
    {
        // Verificar propiedad
        $suscripcion = $this->model->obtener($id, $userId);
        if (!$suscripcion) {
            throw new Exception('Suscripción no encontrada.', 404);
        }

        $resultado = $this->model->eliminar($id);

        if ($resultado) {
            // Notificar eliminación
            try {
                $this->alerts->enviarSuscripcionEliminada($suscripcion);
            } catch (Exception $e) {
                error_log("Error enviando alerta de eliminación: " . $e->getMessage());
            }
        }

        return $resultado;
    }

    /**
     * 6. Cambia el estado de la suscripción (Activar/Desactivar)
     */
    public function cambiarEstado(int $id, int $userId, string $nuevoEstado, ?int $diaCobro = null): array
    {
        // 1. Verificar propiedad y obtener estado actual
        $suscripcion = $this->model->obtener($id, $userId);
        if (!$suscripcion) {
            throw new Exception('Suscripción no encontrada.', 404);
        }

        $datosActualizar = [];
        $nuevoEstadoInput = strtoupper($nuevoEstado); // Normalizar entrada
        $estadoActual = $suscripcion['estado_ahjr']; // 'activa' o 'inactiva'

        // Mapeo de entrada a valor BD
        $estadoDB = ($nuevoEstadoInput === 'ACTIVO') ? 'activa' : 'inactiva';

        // Guardrail: Si el estado es el mismo, no hacer nada
        if ($estadoDB === $estadoActual) {
            return $this->limpiarSufijos($suscripcion);
        }

        // 2. Lógica según estado
        if ($nuevoEstadoInput === 'INACTIVO') {
            $datosActualizar['estado_ahjr'] = 'inactiva';
            $datosActualizar['fecha_proximo_pago_ahjr'] = null;
        } elseif ($nuevoEstadoInput === 'ACTIVO') {
            $datosActualizar['estado_ahjr'] = 'activa';
            $datosActualizar['fecha_ultimo_pago_ahjr'] = date('Y-m-d'); // Fecha actual

            // Si se proporciona día de cobro, actualizarlo
            if ($diaCobro !== null) {
                $datosActualizar['dia_cobro_ahjr'] = $diaCobro;
            }

            // Calcular próximo pago
            $diaCobroCalc = $diaCobro ?? (int)$suscripcion['dia_cobro_ahjr'];
            $frecuencia = $suscripcion['frecuencia_ahjr'];

            $datosActualizar['fecha_proximo_pago_ahjr'] = $this->calcularProximoPago($frecuencia, $diaCobroCalc);
        } else {
            throw new Exception("Estado no válido: {$nuevoEstado}", 400);
        }

        // 3. Guardar cambios
        if (!$this->model->editar($id, $datosActualizar)) {
            throw new Exception("No se pudo actualizar el estado.", 500);
        }

        // Notificar cambio de estado (solo si se desactiva, según requerimiento)
        try {
            if ($nuevoEstadoInput === 'INACTIVO') {
                $this->alerts->enviarSuscripcionDesactivada($suscripcion);
            }
        } catch (Exception $e) {
            error_log("Error enviando alerta de estado: " . $e->getMessage());
        }

        // 4. Retornar suscripción actualizada
        return $this->obtenerDetalle($id, $userId);
    }

    // ===== MÉTODOS PRIVADOS =====

    private function calcularProximoPago(string $frecuencia, int $diaCobro): string
    {
        $fecha = new \DateTime(); // Hoy
        $frecuencia = strtoupper($frecuencia);

        if ($frecuencia === 'MENSUAL') {
            $fecha->modify('+1 month');
        } elseif ($frecuencia === 'ANUAL') {
            $fecha->modify('+1 year');
        } elseif ($frecuencia === 'SEMANAL') {
            $fecha->modify('+1 week');
            return $fecha->format('Y-m-d');
        }

        // Ajustar el día para Mensual/Anual
        $year = (int)$fecha->format('Y');
        $month = (int)$fecha->format('m');

        // Validar si el día existe en ese mes (ej: 31 Feb)
        if (!checkdate($month, $diaCobro, $year)) {
            // Si no existe, tomar el último día del mes
            $diaCobro = (int)$fecha->format('t');
        }

        $fecha->setDate($year, $month, $diaCobro);

        return $fecha->format('Y-m-d');
    }

    private function limpiarSufijos(array $datos): array
    {
        return [
            'id' => (int) $datos['id_suscripcion_ahjr'],
            'nombre_servicio' => $datos['nombre_servicio_ahjr'],
            'costo' => (float) $datos['costo_ahjr'],
            'estado' => $datos['estado_ahjr'],
            'frecuencia' => $datos['frecuencia_ahjr'],
            'metodo_pago' => $datos['metodo_pago_ahjr'],
            'dia_cobro' => $datos['dia_cobro_ahjr'] ? (int) $datos['dia_cobro_ahjr'] : null,
            'mes_cobro' => $datos['mes_cobro_ahjr'] ? (int) $datos['mes_cobro_ahjr'] : null,
            'fecha_ultimo_pago' => $datos['fecha_ultimo_pago_ahjr'],
            'fecha_proximo_pago' => $datos['fecha_proximo_pago_ahjr'] ?? null,
            'dias_restantes' => isset($datos['dias_restantes_ahjr']) ? (int) $datos['dias_restantes_ahjr'] : null,
            'fecha_creacion' => $datos['fecha_creacion_ahjr']
        ];
    }
}
