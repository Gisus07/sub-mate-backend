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
    private PDO $db_AHJR;

    public function __construct()
    {
        $this->db_AHJR = Database::getDB_AHJR();
    }

    /**
     * 1. Obtiene gasto total del mes (LÓGICA HÍBRIDA)
     * 
     * - Mes pasado: Solo suma historial real
     * - Mes actual: Historial + proyección de suscripciones activas
     */
    public function obtenerGastoTotalMes_AHJR(int $uid_AHJR, int $mes_AHJR, int $anio_AHJR): float
    {
        $mesActual_AHJR = (int) date('n');
        $anioActual_AHJR =  (int) date('Y');

        // Determinar si es mes pasado o actual
        $esMesPasado_AHJR = ($anio_AHJR < $anioActual_AHJR) || ($anio_AHJR === $anioActual_AHJR && $mes_AHJR < $mesActual_AHJR);

        if ($esMesPasado_AHJR) {
            // CASO 1: Mes pasado - Solo historial
            $sql_AHJR = "SELECT COALESCE(SUM(h.monto_pagado_ahjr), 0) as total
                    FROM td_historial_pagos_ahjr h
                    INNER JOIN td_suscripciones_ahjr s 
                        ON h.id_suscripcion_historial_ahjr = s.id_suscripcion_ahjr
                    WHERE s.id_usuario_suscripcion_ahjr = :uid
                    AND MONTH(h.fecha_pago_ahjr) = :mes
                    AND YEAR(h.fecha_pago_ahjr) = :anio";

            $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
            $stmt_AHJR->execute(['uid' => $uid_AHJR, 'mes' => $mes_AHJR, 'anio' => $anio_AHJR]);
        } else {
            // CASO 2: Mes actual - Historial + Proyección de activas
            // Usar parámetros posicionales para evitar duplicados
            $sql_AHJR = "SELECT 
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

            $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
            $stmt_AHJR->execute([$uid_AHJR, $mes_AHJR, $anio_AHJR, $uid_AHJR]);
        }

        $result_AHJR = $stmt_AHJR->fetch();
        return (float) $result_AHJR['total'];
    }

    /**
     * 2. Obtiene gasto agrupado por categoría (frecuencia)
     */
    public function obtenerGastoPorCategoria_AHJR(int $uid_AHJR): array
    {
        $sql_AHJR = "SELECT 
                    frecuencia_ahjr,
                    SUM(costo_ahjr) as total
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid
                AND estado_ahjr = 'activa'
                GROUP BY frecuencia_ahjr";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute(['uid' => $uid_AHJR]);

        $resultado_AHJR = ['mensual' => 0.0, 'anual' => 0.0];

        while ($row_AHJR = $stmt_AHJR->fetch()) {
            $resultado_AHJR[$row_AHJR['frecuencia_ahjr']] = (float) $row_AHJR['total'];
        }

        return $resultado_AHJR;
    }

    /**
     * 3. Obtiene historial anual (Enero - Diciembre del año actual)
     * 
     * Lógica Híbrida:
     * - Meses pasados: Suma de historial real.
     * - Mes actual: Suma de historial (pagado) + Proyección (por pagar).
     */
    public function obtenerHistorialAnual_AHJR(int $uid_AHJR): array
    {
        $anioActual_AHJR = (int) date('Y');
        $mesActual_AHJR = (int) date('n');

        // 1. Obtener historial real de todo el año (Ene - Actual)
        $sqlHistorial_AHJR = "SELECT 
                            DATE_FORMAT(h.fecha_pago_ahjr, '%Y-%m') as mes,
                            SUM(h.monto_pagado_ahjr) as total
                        FROM td_historial_pagos_ahjr h
                        INNER JOIN td_suscripciones_ahjr s 
                            ON h.id_suscripcion_historial_ahjr = s.id_suscripcion_ahjr
                        WHERE s.id_usuario_suscripcion_ahjr = :uid
                        AND YEAR(h.fecha_pago_ahjr) = :anio
                        GROUP BY DATE_FORMAT(h.fecha_pago_ahjr, '%Y-%m')";

        $stmt_AHJR = $this->db_AHJR->prepare($sqlHistorial_AHJR);
        $stmt_AHJR->execute(['uid' => $uid_AHJR, 'anio' => $anioActual_AHJR]);

        $historial_AHJR = $stmt_AHJR->fetchAll(PDO::FETCH_KEY_PAIR); // ['2024-01' => 100, ...]

        // 2. Calcular proyección "Por Pagar" del mes actual
        // Suscripciones activas cuya fecha de próximo pago cae en este mes y año
        $sqlProyeccion_AHJR = "SELECT COALESCE(SUM(costo_ahjr), 0) as total
                          FROM td_suscripciones_ahjr
                          WHERE id_usuario_suscripcion_ahjr = :uid
                          AND estado_ahjr = 'activa'
                          AND MONTH(fecha_proximo_pago_ahjr) = :mes
                          AND YEAR(fecha_proximo_pago_ahjr) = :anio";

        $stmtProj_AHJR = $this->db_AHJR->prepare($sqlProyeccion_AHJR);
        $stmtProj_AHJR->execute(['uid' => $uid_AHJR, 'mes' => $mesActual_AHJR, 'anio' => $anioActual_AHJR]);
        $porPagar_AHJR = (float) $stmtProj_AHJR->fetch()['total'];

        // 3. Sumar proyección al mes actual en el historial
        $mesActualKey_AHJR = date('Y-m');
        if (!isset($historial_AHJR[$mesActualKey_AHJR])) {
            $historial_AHJR[$mesActualKey_AHJR] = 0;
        }
        $historial_AHJR[$mesActualKey_AHJR] += $porPagar_AHJR;

        return $historial_AHJR;
    }

    /**
     * 4. Cuenta suscripciones activas
     */
    public function contarActivas_AHJR(int $uid_AHJR): int
    {
        $sql_AHJR = "SELECT COUNT(*) as total
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid
                AND estado_ahjr = 'activa'";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute(['uid' => $uid_AHJR]);

        $result_AHJR = $stmt_AHJR->fetch();
        return (int) $result_AHJR['total'];
    }

    /**
     * 5. Obtiene próximo vencimiento (día de cobro más cercano)
     */
    public function obtenerProximoVencimiento_AHJR(int $uid_AHJR): ?array
    {
        $diaActual_AHJR = (int) date('j');

        $sql_AHJR = "SELECT 
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

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute([$uid_AHJR, $diaActual_AHJR, $diaActual_AHJR, $diaActual_AHJR]);

        $result_AHJR = $stmt_AHJR->fetch();
        return $result_AHJR ?: null;
    }

    /**
     * MÉTODO ADICIONAL para prepararDistribucionMetodos (usado por DashboardService)
     * Obtiene distribución de gastos por método de pago desde suscripciones activas
     */
    public function obtenerDistribucionPorMetodo_AHJR(int $uid_AHJR): array
    {
        $sql_AHJR = "SELECT 
                    metodo_pago_ahjr, 
                    SUM(costo_ahjr) as total
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid 
                    AND estado_ahjr = 'activa'
                GROUP BY metodo_pago_ahjr
                ORDER BY total DESC";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute(['uid' => $uid_AHJR]);

        return $stmt_AHJR->fetchAll();
    }

    /**
     * NUEVO: Obtiene gasto mensual estimado
     * Suma de costos mensuales + (costos anuales / 12)
     */
    public function obtenerGastoMensualEstimado_AHJR(int $uid_AHJR): float
    {
        // 1. Sumar mensuales
        $sqlMensual_AHJR = "SELECT COALESCE(SUM(costo_ahjr), 0) as total
                       FROM td_suscripciones_ahjr
                       WHERE id_usuario_suscripcion_ahjr = :uid
                       AND estado_ahjr = 'activa'
                       AND frecuencia_ahjr = 'mensual'";

        $stmtMensual_AHJR = $this->db_AHJR->prepare($sqlMensual_AHJR);
        $stmtMensual_AHJR->execute(['uid' => $uid_AHJR]);
        $totalMensual_AHJR = (float) $stmtMensual_AHJR->fetch()['total'];

        // 2. Sumar anuales y dividir por 12
        $sqlAnual_AHJR = "SELECT COALESCE(SUM(costo_ahjr), 0) as total
                     FROM td_suscripciones_ahjr
                     WHERE id_usuario_suscripcion_ahjr = :uid
                     AND estado_ahjr = 'activa'
                     AND frecuencia_ahjr = 'anual'";

        $stmtAnual_AHJR = $this->db_AHJR->prepare($sqlAnual_AHJR);
        $stmtAnual_AHJR->execute(['uid' => $uid_AHJR]);
        $totalAnual_AHJR = (float) $stmtAnual_AHJR->fetch()['total'];

        return $totalMensual_AHJR + ($totalAnual_AHJR / 12);
    }

    /**
     * NUEVO: Obtiene distribución por frecuencia (para Donut Chart)
     */
    public function obtenerDistribucionFrecuencia_AHJR(int $uid_AHJR): array
    {
        $sql_AHJR = "SELECT 
                    frecuencia_ahjr,
                    COUNT(*) as total
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid
                AND estado_ahjr = 'activa'
                GROUP BY frecuencia_ahjr";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute(['uid' => $uid_AHJR]);

        return $stmt_AHJR->fetchAll(PDO::FETCH_KEY_PAIR); // Retorna ['mensual' => 5, 'anual' => 1]
    }
    /**
     * NUEVO: Obtiene las N suscripciones más costosas
     */
    public function obtenerTopSuscripciones_AHJR(int $uid_AHJR, int $limit_AHJR): array
    {
        $sql_AHJR = "SELECT 
                    nombre_servicio_ahjr as nombre,
                    costo_ahjr as costo
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid
                AND estado_ahjr = 'activa'
                ORDER BY costo_ahjr DESC
                LIMIT :limit";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        // Bind manual para LIMIT (PDO a veces da problemas con strings en LIMIT)
        $stmt_AHJR->bindValue(':uid', $uid_AHJR, PDO::PARAM_INT);
        $stmt_AHJR->bindValue(':limit', $limit_AHJR, PDO::PARAM_INT);
        $stmt_AHJR->execute();

        return $stmt_AHJR->fetchAll(PDO::FETCH_ASSOC);
    }
}
