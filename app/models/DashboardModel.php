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
     * 3. Obtiene historial anual (Enero - Diciembre del año actual)
     * 
     * Lógica Híbrida:
     * - Meses pasados: Suma de historial real.
     * - Mes actual: Suma de historial (pagado) + Proyección (por pagar).
     */
    public function obtenerHistorialAnual(int $uid): array
    {
        $anioActual = (int) date('Y');
        $mesActual = (int) date('n');

        // 1. Obtener historial real de todo el año (Ene - Actual)
        $sqlHistorial = "SELECT 
                            DATE_FORMAT(h.fecha_pago_ahjr, '%Y-%m') as mes,
                            SUM(h.monto_pagado_ahjr) as total
                        FROM td_historial_pagos_ahjr h
                        INNER JOIN td_suscripciones_ahjr s 
                            ON h.id_suscripcion_historial_ahjr = s.id_suscripcion_ahjr
                        WHERE s.id_usuario_suscripcion_ahjr = :uid
                        AND YEAR(h.fecha_pago_ahjr) = :anio
                        GROUP BY DATE_FORMAT(h.fecha_pago_ahjr, '%Y-%m')";

        $stmt = $this->db->prepare($sqlHistorial);
        $stmt->execute(['uid' => $uid, 'anio' => $anioActual]);

        $historial = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['2024-01' => 100, ...]

        // 2. Calcular proyección "Por Pagar" del mes actual
        // Suscripciones activas cuya fecha de próximo pago cae en este mes y año
        $sqlProyeccion = "SELECT COALESCE(SUM(costo_ahjr), 0) as total
                          FROM td_suscripciones_ahjr
                          WHERE id_usuario_suscripcion_ahjr = :uid
                          AND estado_ahjr = 'activa'
                          AND MONTH(fecha_proximo_pago_ahjr) = :mes
                          AND YEAR(fecha_proximo_pago_ahjr) = :anio";

        $stmtProj = $this->db->prepare($sqlProyeccion);
        $stmtProj->execute(['uid' => $uid, 'mes' => $mesActual, 'anio' => $anioActual]);
        $porPagar = (float) $stmtProj->fetch()['total'];

        // 3. Sumar proyección al mes actual en el historial
        $mesActualKey = date('Y-m');
        if (!isset($historial[$mesActualKey])) {
            $historial[$mesActualKey] = 0;
        }
        $historial[$mesActualKey] += $porPagar;

        return $historial;
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
     * Obtiene distribución de gastos por método de pago desde suscripciones activas
     */
    public function obtenerDistribucionPorMetodo(int $uid): array
    {
        $sql = "SELECT 
                    metodo_pago_ahjr, 
                    SUM(costo_ahjr) as total
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid 
                    AND estado_ahjr = 'activa'
                GROUP BY metodo_pago_ahjr
                ORDER BY total DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $uid]);

        return $stmt->fetchAll();
    }

    /**
     * NUEVO: Obtiene gasto mensual estimado
     * Suma de costos mensuales + (costos anuales / 12)
     */
    public function obtenerGastoMensualEstimado(int $uid): float
    {
        // 1. Sumar mensuales
        $sqlMensual = "SELECT COALESCE(SUM(costo_ahjr), 0) as total
                       FROM td_suscripciones_ahjr
                       WHERE id_usuario_suscripcion_ahjr = :uid
                       AND estado_ahjr = 'activa'
                       AND frecuencia_ahjr = 'mensual'";

        $stmtMensual = $this->db->prepare($sqlMensual);
        $stmtMensual->execute(['uid' => $uid]);
        $totalMensual = (float) $stmtMensual->fetch()['total'];

        // 2. Sumar anuales y dividir por 12
        $sqlAnual = "SELECT COALESCE(SUM(costo_ahjr), 0) as total
                     FROM td_suscripciones_ahjr
                     WHERE id_usuario_suscripcion_ahjr = :uid
                     AND estado_ahjr = 'activa'
                     AND frecuencia_ahjr = 'anual'";

        $stmtAnual = $this->db->prepare($sqlAnual);
        $stmtAnual->execute(['uid' => $uid]);
        $totalAnual = (float) $stmtAnual->fetch()['total'];

        return $totalMensual + ($totalAnual / 12);
    }

    /**
     * NUEVO: Obtiene distribución por frecuencia (para Donut Chart)
     */
    public function obtenerDistribucionFrecuencia(int $uid): array
    {
        $sql = "SELECT 
                    frecuencia_ahjr,
                    COUNT(*) as total
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid
                AND estado_ahjr = 'activa'
                GROUP BY frecuencia_ahjr";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $uid]);

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Retorna ['mensual' => 5, 'anual' => 1]
    }
    /**
     * NUEVO: Obtiene las N suscripciones más costosas
     */
    public function obtenerTopSuscripciones(int $uid, int $limit): array
    {
        $sql = "SELECT 
                    nombre_servicio_ahjr as nombre,
                    costo_ahjr as costo
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid
                AND estado_ahjr = 'activa'
                ORDER BY costo_ahjr DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        // Bind manual para LIMIT (PDO a veces da problemas con strings en LIMIT)
        $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
