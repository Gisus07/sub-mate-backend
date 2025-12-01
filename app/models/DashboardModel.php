<?php

declare(strict_types=1);

namespace App\models;

use App\core\Database;
use PDO;

/**
 * DashboardModel - Consultas Analíticas (Read-Only)
 *  
 * RESTRICCIÓN: Exactamente 5 métodos públicos
 * Responsabilidad: Obtener datos agregados de historial y suscripciones
 */
class DashboardModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getDB();
    }

    /**
     * 1. Obtiene gasto total del mes (LÓGICA HÍBRIDA)
     * 
     * - Mes pasado: Solo suma historial real
     * - Mes actual: Historial + proyección de suscripciones activas
     */
    public function obtenerGastoTotalMes(int $uid, int $mes, int $anio): float
    {
        $mesActual = (int) date('n');
        $anioActual =  (int) date('Y');

        // Determinar si es mes pasado o actual
        $esMesPasado = ($anio < $anioActual) || ($anio === $anioActual && $mes < $mesActual);

        if ($esMesPasado) {
            // CASO 1: Mes pasado - Solo historial
            $sql = "SELECT COALESCE(SUM(h.monto_pagado_ahjr), 0) as total
                    FROM td_historial_pagos_ahjr h
                    INNER JOIN td_suscripciones_ahjr s 
                        ON h.id_suscripcion_historial_ahjr = s.id_suscripcion_ahjr
                    WHERE s.id_usuario_suscripcion_ahjr = :uid
                    AND MONTH(h.fecha_pago_ahjr) = :mes
                    AND YEAR(h.fecha_pago_ahjr) = :anio";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['uid' => $uid, 'mes' => $mes, 'anio' => $anio]);
        } else {
            // CASO 2: Mes actual - Historial + Proyección de activas
            // Usar parámetros posicionales para evitar duplicados
            $sql = "SELECT 
                        (SELECT COALESCE(SUM(h.monto_pagado_ahjr), 0)
                         FROM td_historial_pagos_ahjr h
                         INNER JOIN td_suscripciones_ahjr s 
                            ON h.id_suscripcion_historial_ahjr = s.id_suscripcion_ahjr
                         WHERE s.id_usuario_suscripcion_ahjr = ?
                         AND MONTH(h.fecha_pago_ahjr) = ?
                         AND YEAR(h.fecha_pago_ahjr) = ?)
                        +
                        (SELECT COALESCE(SUM(costo_ahjr), 0)
                         FROM td_suscripciones_ahjr
                         WHERE id_usuario_suscripcion_ahjr = ?
                         AND estado_ahjr = 'activa'
                         AND frecuencia_ahjr = 'mensual')
                    as total";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$uid, $mes, $anio, $uid]);
        }

        $result = $stmt->fetch();
        return (float) $result['total'];
    }

    /**
     * 2. Obtiene gasto agrupado por categoría (frecuencia)
     */
    public function obtenerGastoPorCategoria(int $uid): array
    {
        $sql = "SELECT 
                    frecuencia_ahjr,
                    SUM(costo_ahjr) as total
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid
                AND estado_ahjr = 'activa'
                GROUP BY frecuencia_ahjr";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $uid]);

        $resultado = ['mensual' => 0.0, 'anual' => 0.0];

        while ($row = $stmt->fetch()) {
            $resultado[$row['frecuencia_ahjr']] = (float) $row['total'];
        }

        return $resultado;
    }

    /**
     * 3. Obtiene historial de pagos de los últimos 6 meses
     * 
     * Retorna: ['2024-06' => 120.50, '2024-07' => 135.00, ...]
     */
    public function obtenerHistorialUltimos6Meses(int $uid): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(h.fecha_pago_ahjr, '%Y-%m') as mes,
                    SUM(h.monto_pagado_ahjr) as total
                FROM td_historial_pagos_ahjr h
                INNER JOIN td_suscripciones_ahjr s 
                    ON h.id_suscripcion_historial_ahjr = s.id_suscripcion_ahjr
                WHERE s.id_usuario_suscripcion_ahjr = :uid
                AND h.fecha_pago_ahjr >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(h.fecha_pago_ahjr, '%Y-%m')
                ORDER BY mes ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $uid]);

        $resultado = [];
        while ($row = $stmt->fetch()) {
            $resultado[$row['mes']] = (float) $row['total'];
        }

        return $resultado;
    }

    /**
     * 4. Cuenta suscripciones activas
     */
    public function contarActivas(int $uid): int
    {
        $sql = "SELECT COUNT(*) as total
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid
                AND estado_ahjr = 'activa'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $uid]);

        $result = $stmt->fetch();
        return (int) $result['total'];
    }

    /**
     * 5. Obtiene próximo vencimiento (día de cobro más cercano)
     */
    public function obtenerProximoVencimiento(int $uid): ?array
    {
        $diaActual = (int) date('j');

        $sql = "SELECT 
                    id_suscripcion_ahjr,
                    nombre_servicio_ahjr,
                    costo_ahjr,
                    dia_cobro_ahjr,
                    mes_cobro_ahjr,
                    frecuencia_ahjr,
                    fecha_ultimo_pago_ahjr
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = ?
                AND estado_ahjr = 'activa'
                ORDER BY 
                    CASE 
                        WHEN dia_cobro_ahjr >= ? THEN dia_cobro_ahjr - ?
                        ELSE (31 - ?) + dia_cobro_ahjr
                    END ASC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$uid, $diaActual, $diaActual, $diaActual]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * MÉTODO ADICIONAL para prepararDistribucionMetodos (usado por DashboardService)
     * Obtiene distribución de gastos por método de pago desde el historial
     */
    public function obtenerDistribucionPorMetodo(int $uid): array
    {
        $sql = "SELECT 
                    h.metodo_pago_snapshot_ahjr as metodo,
                    SUM(h.monto_pagado_ahjr) as total
                FROM td_historial_pagos_ahjr h
                INNER JOIN td_suscripciones_ahjr s 
                    ON h.id_suscripcion_historial_ahjr = s.id_suscripcion_ahjr
                WHERE s.id_usuario_suscripcion_ahjr = :uid
                GROUP BY h.metodo_pago_snapshot_ahjr
                ORDER BY total DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $uid]);

        return $stmt->fetchAll();
    }
}
