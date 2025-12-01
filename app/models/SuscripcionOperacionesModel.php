<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * SuscripcionOperacionesModel - Operaciones Especiales
 * 
 * RESTRICCIÓN: Máximo 5 métodos públicos (tiene 2)
 */
class SuscripcionOperacionesModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getDB();
    }

    /**
     * 1. Cambia estado de suscripción con lógica de negocio
     * 
     * @return array|bool Retorna datos actualizados o false
     */
    public function cambiarEstado(int $id, string $estado, int $uid, ?string $frecuenciaElegida = null, ?int $diaCobro = null, ?int $mesCobro = null, ?float $costo = null)
    {
        $estado = strtolower($estado);

        if ($estado === 'inactiva' || $estado === 'inactivo') {
            // Lógica DESACTIVACIÓN: Sanitizar fechas y ciclo
            $sql = "UPDATE td_suscripciones_ahjr 
                    SET estado_ahjr = 'inactiva',
                        fecha_proximo_pago_ahjr = NULL,
                        dia_cobro_ahjr = NULL,
                        mes_cobro_ahjr = NULL
                    WHERE id_suscripcion_ahjr = :id AND id_usuario_suscripcion_ahjr = :uid";
            $stmt = $this->db->prepare($sql);
            if ($stmt->execute(['id' => $id, 'uid' => $uid])) {
                return $this->obtenerDatosActualizados($id, $uid);
            }
            return false;
        } elseif ($estado === 'activa' || $estado === 'activo') {
            // Lógica REACTIVACIÓN

            // 1. Obtener datos actuales
            $stmtInfo = $this->db->prepare("SELECT frecuencia_ahjr, costo_ahjr FROM td_suscripciones_ahjr WHERE id_suscripcion_ahjr = :id AND id_usuario_suscripcion_ahjr = :uid");
            $stmtInfo->execute(['id' => $id, 'uid' => $uid]);
            $info = $stmtInfo->fetch();

            if (!$info) return false;

            // 2. Determinar frecuencia final
            $frecuenciaFinal = $frecuenciaElegida ?? $info['frecuencia_ahjr'];

            // 3. Usar día/mes proporcionados (deben ser HOY por regla de negocio)
            if ($diaCobro === null) {
                $diaCobro = (int)date('d');
            }

            // 4. Determinar costo final (si se pasa null, mantiene el actual)
            $costoFinal = $costo ?? (float)$info['costo_ahjr'];

            // 5. Calcular fechas (Delegado a MySQL para precisión en días 29, 30, 31)
            $fechaUltimoPago = date('Y-m-d'); // Hoy

            // 6. Actualizar con nuevos ciclos y costo
            // Usamos DATE_ADD de MySQL que maneja correctamente el desbordamiento de meses
            // Ejemplo: 31 Enero + 1 Mes = 28/29 Febrero (automático en MySQL)
            $intervalo = ($frecuenciaFinal === 'mensual') ? '1 MONTH' : '1 YEAR';

            $sql = "UPDATE td_suscripciones_ahjr 
                    SET estado_ahjr = 'activa',
                        frecuencia_ahjr = :frecuencia,
                        costo_ahjr = :costo,
                        fecha_ultimo_pago_ahjr = :ultimo,
                        fecha_proximo_pago_ahjr = DATE_ADD(:ultimo_calc, INTERVAL $intervalo), 
                        dia_cobro_ahjr = :dia,
                        mes_cobro_ahjr = :mes
                    WHERE id_suscripcion_ahjr = :id AND id_usuario_suscripcion_ahjr = :uid";

            $stmt = $this->db->prepare($sql);
            if ($stmt->execute([
                'frecuencia' => $frecuenciaFinal,
                'costo' => $costoFinal,
                'ultimo' => $fechaUltimoPago,
                'ultimo_calc' => $fechaUltimoPago, // Se pasa dos veces, una para el campo y otra para el cálculo
                'dia' => $diaCobro,
                'mes' => $mesCobro,
                'id' => $id,
                'uid' => $uid
            ])) {
                return $this->obtenerDatosActualizados($id, $uid);
            }
        }

        return false;
    }

    // Método calcularProximaFecha eliminado ya que la lógica se movió a MySQL


    private function obtenerDatosActualizados(int $id, int $uid): array
    {
        $sql = "SELECT *, DATEDIFF(fecha_proximo_pago_ahjr, CURDATE()) as dias_restantes_ahjr 
                FROM td_suscripciones_ahjr 
                WHERE id_suscripcion_ahjr = :id AND id_usuario_suscripcion_ahjr = :uid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'uid' => $uid]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 2. Registra pago simulado con transacción
     */
    public function registrarPagoSimulado(int $id, float $monto, string $metodo, string $frecuencia): bool
    {
        try {
            $this->db->beginTransaction();

            // Calcular intervalo según frecuencia
            $intervalo = $frecuencia === 'mensual' ? '1 MONTH' : '1 YEAR';

            // Actualizar fecha de último pago a HOY y calcular próximo pago
            $sqlUpdate = "UPDATE td_suscripciones_ahjr 
                          SET fecha_ultimo_pago_ahjr = CURDATE(),
                              fecha_proximo_pago_ahjr = DATE_ADD(CURDATE(), INTERVAL {$intervalo})
                          WHERE id_suscripcion_ahjr = :id";

            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute(['id' => $id]);

            // Obtener fecha de pago (HOY) para el historial
            $nuevaFecha = date('Y-m-d');

            // Insertar en historial
            $sqlHistorial = "INSERT INTO td_historial_pagos_ahjr 
                             (id_suscripcion_historial_ahjr, monto_pagado_ahjr, fecha_pago_ahjr, metodo_pago_snapshot_ahjr)
                             VALUES (:id, :monto, :fecha, :metodo)";

            $stmtHistorial = $this->db->prepare($sqlHistorial);
            $stmtHistorial->execute([
                'id' => $id,
                'monto' => $monto,
                'fecha' => $nuevaFecha,
                'metodo' => $metodo
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
