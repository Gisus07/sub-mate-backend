<?php

declare(strict_types=1);

namespace App\models;

use App\core\Database;
use PDO;

/**
 * SuscripcionOperacionesModel - Operaciones Especiales
 * 
 * RESTRICCIÓN: Máximo 5 métodos públicos (tiene 2)
 */
class SuscripcionOperacionesModel
{
    private PDO $db_AHJR;

    public function __construct()
    {
        $this->db_AHJR = Database::getDB_AHJR();
    }

    /**
     * 1. Cambia estado de suscripción con lógica de negocio
     * 
     * @return array|bool Retorna datos actualizados o false
     */
    public function cambiarEstado_AHJR(int $id_AHJR, string $estado_AHJR, int $uid_AHJR, ?string $frecuenciaElegida_AHJR = null, ?int $diaCobro_AHJR = null, ?int $mesCobro_AHJR = null, ?float $costo_AHJR = null)
    {
        $estado_AHJR = strtolower($estado_AHJR);

        if ($estado_AHJR === 'inactiva' || $estado_AHJR === 'inactivo') {
            // Lógica DESACTIVACIÓN: Sanitizar fechas y ciclo
            $sql_AHJR = "UPDATE td_suscripciones_ahjr 
                    SET estado_ahjr = 'inactiva',
                        fecha_proximo_pago_ahjr = NULL,
                        dia_cobro_ahjr = NULL,
                        mes_cobro_ahjr = NULL
                    WHERE id_suscripcion_ahjr = :id AND id_usuario_suscripcion_ahjr = :uid";
            $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
            if ($stmt_AHJR->execute(['id' => $id_AHJR, 'uid' => $uid_AHJR])) {
                return $this->obtenerDatosActualizados_AHJR($id_AHJR, $uid_AHJR);
            }
            return false;
        } elseif ($estado_AHJR === 'activa' || $estado_AHJR === 'activo') {
            // Lógica REACTIVACIÓN

            // 1. Obtener datos actuales
            $stmtInfo_AHJR = $this->db_AHJR->prepare("SELECT frecuencia_ahjr, costo_ahjr FROM td_suscripciones_ahjr WHERE id_suscripcion_ahjr = :id AND id_usuario_suscripcion_ahjr = :uid");
            $stmtInfo_AHJR->execute(['id' => $id_AHJR, 'uid' => $uid_AHJR]);
            $info_AHJR = $stmtInfo_AHJR->fetch();

            if (!$info_AHJR) return false;

            // 2. Determinar frecuencia final
            $frecuenciaFinal_AHJR = $frecuenciaElegida_AHJR ?? $info_AHJR['frecuencia_ahjr'];

            // 3. Usar día/mes proporcionados (deben ser HOY por regla de negocio)
            if ($diaCobro_AHJR === null) {
                $diaCobro_AHJR = (int)date('d');
            }

            // 4. Determinar costo final (si se pasa null, mantiene el actual)
            $costoFinal_AHJR = $costo_AHJR ?? (float)$info_AHJR['costo_ahjr'];

            // 5. Calcular fechas (Delegado a MySQL para precisión en días 29, 30, 31)
            $fechaUltimoPago_AHJR = date('Y-m-d'); // Hoy

            // 6. Actualizar con nuevos ciclos y costo
            // Usamos DATE_ADD de MySQL que maneja correctamente el desbordamiento de meses
            // Ejemplo: 31 Enero + 1 Mes = 28/29 Febrero (automático en MySQL)
            $intervalo_AHJR = ($frecuenciaFinal_AHJR === 'mensual') ? '1 MONTH' : '1 YEAR';

            $sql_AHJR = "UPDATE td_suscripciones_ahjr 
                    SET estado_ahjr = 'activa',
                        frecuencia_ahjr = :frecuencia,
                        costo_ahjr = :costo,
                        fecha_ultimo_pago_ahjr = :ultimo,
                        fecha_proximo_pago_ahjr = DATE_ADD(:ultimo_calc, INTERVAL $intervalo_AHJR), 
                        dia_cobro_ahjr = :dia,
                        mes_cobro_ahjr = :mes
                    WHERE id_suscripcion_ahjr = :id AND id_usuario_suscripcion_ahjr = :uid";

            $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
            if ($stmt_AHJR->execute([
                'frecuencia' => $frecuenciaFinal_AHJR,
                'costo' => $costoFinal_AHJR,
                'ultimo' => $fechaUltimoPago_AHJR,
                'ultimo_calc' => $fechaUltimoPago_AHJR, // Se pasa dos veces, una para el campo y otra para el cálculo
                'dia' => $diaCobro_AHJR,
                'mes' => $mesCobro_AHJR,
                'id' => $id_AHJR,
                'uid' => $uid_AHJR
            ])) {
                return $this->obtenerDatosActualizados_AHJR($id_AHJR, $uid_AHJR);
            }
        }

        return false;
    }

    // Método calcularProximaFecha eliminado ya que la lógica se movió a MySQL


    private function obtenerDatosActualizados_AHJR(int $id_AHJR, int $uid_AHJR): array
    {
        $sql_AHJR = "SELECT *, DATEDIFF(fecha_proximo_pago_ahjr, CURDATE()) as dias_restantes_ahjr 
                FROM td_suscripciones_ahjr 
                WHERE id_suscripcion_ahjr = :id AND id_usuario_suscripcion_ahjr = :uid";
        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute(['id' => $id_AHJR, 'uid' => $uid_AHJR]);
        return $stmt_AHJR->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 2. Registra pago simulado con transacción
     */
    public function registrarPagoSimulado_AHJR(int $id_AHJR, float $monto_AHJR, string $metodo_AHJR, string $frecuencia_AHJR): bool
    {
        try {
            $this->db_AHJR->beginTransaction();

            // Calcular intervalo según frecuencia
            $intervalo_AHJR = $frecuencia_AHJR === 'mensual' ? '1 MONTH' : '1 YEAR';

            // Actualizar fecha de último pago a HOY y calcular próximo pago
            $sqlUpdate_AHJR = "UPDATE td_suscripciones_ahjr 
                          SET fecha_ultimo_pago_ahjr = CURDATE(),
                              fecha_proximo_pago_ahjr = DATE_ADD(CURDATE(), INTERVAL {$intervalo_AHJR})
                          WHERE id_suscripcion_ahjr = :id";

            $stmtUpdate_AHJR = $this->db_AHJR->prepare($sqlUpdate_AHJR);
            $stmtUpdate_AHJR->execute(['id' => $id_AHJR]);

            // Obtener fecha de pago (HOY) para el historial
            $nuevaFecha_AHJR = date('Y-m-d');

            // Insertar en historial
            $sqlHistorial_AHJR = "INSERT INTO td_historial_pagos_ahjr 
                             (id_suscripcion_historial_ahjr, monto_pagado_ahjr, fecha_pago_ahjr, metodo_pago_snapshot_ahjr)
                             VALUES (:id, :monto, :fecha, :metodo)";

            $stmtHistorial_AHJR = $this->db_AHJR->prepare($sqlHistorial_AHJR);
            $stmtHistorial_AHJR->execute([
                'id' => $id_AHJR,
                'monto' => $monto_AHJR,
                'fecha' => $nuevaFecha_AHJR,
                'metodo' => $metodo_AHJR
            ]);

            $this->db_AHJR->commit();
            return true;
        } catch (\Exception $e) {
            $this->db_AHJR->rollBack();
            throw $e;
        }
    }

    /**
     * 3. Registra pago histórico manual (para creación de suscripción)
     */
    public function registrarPagoHistoricoManual_AHJR(int $id_AHJR, float $monto_AHJR, string $fecha_AHJR, string $metodo_AHJR): bool
    {
        $sql_AHJR = "INSERT INTO td_historial_pagos_ahjr 
                (id_suscripcion_historial_ahjr, monto_pagado_ahjr, fecha_pago_ahjr, metodo_pago_snapshot_ahjr)
                VALUES (:id, :monto, :fecha, :metodo)";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        return $stmt_AHJR->execute([
            'id' => $id_AHJR,
            'monto' => $monto_AHJR,
            'fecha' => $fecha_AHJR,
            'metodo' => $metodo_AHJR
        ]);
    }
}
