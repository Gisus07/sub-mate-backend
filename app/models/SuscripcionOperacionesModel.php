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
     * 1. Cambia estado de suscripción
     */
    public function cambiarEstado(int $id, string $estado): bool
    {
        $sql = "UPDATE td_suscripciones_ahjr 
                SET estado_ahjr = :estado 
                WHERE id_suscripcion_ahjr = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['estado' => $estado, 'id' => $id]);
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

            // Actualizar fecha de último pago
            $sqlUpdate = "UPDATE td_suscripciones_ahjr 
                          SET fecha_ultimo_pago_ahjr = DATE_ADD(fecha_ultimo_pago_ahjr, INTERVAL {$intervalo})
                          WHERE id_suscripcion_ahjr = :id";

            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute(['id' => $id]);

            // Obtener nueva fecha para el historial
            $sqlFecha = "SELECT fecha_ultimo_pago_ahjr FROM td_suscripciones_ahjr WHERE id_suscripcion_ahjr = :id";
            $stmtFecha = $this->db->prepare($sqlFecha);
            $stmtFecha->execute(['id' => $id]);
            $nuevaFecha = $stmtFecha->fetchColumn();

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
