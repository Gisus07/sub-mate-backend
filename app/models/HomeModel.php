<?php

declare(strict_types=1);

namespace App\models;

use App\core\Database;
use PDO;

class HomeModel
{
    private PDO $db_AHJR;

    public function __construct()
    {
        $this->db_AHJR = Database::getDB_AHJR();
    }

    /**
     * Obtiene la suma de cobros en los próximos 7 días.
     */
    public function obtenerGastoProximos7Dias_AHJR(int $uid_AHJR): float
    {
        $sql_AHJR = "SELECT SUM(costo_ahjr) as total 
                FROM td_suscripciones_ahjr 
                WHERE id_usuario_suscripcion_ahjr = :uid 
                AND estado_ahjr = 'activa'
                AND fecha_proximo_pago_ahjr BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute(['uid' => $uid_AHJR]);

        $result_AHJR = $stmt_AHJR->fetch();
        return (float) ($result_AHJR['total'] ?? 0);
    }

    /**
     * Busca la suscripción activa con mayor costo este mes.
     */
    public function obtenerProximoGranCargo_AHJR(int $uid_AHJR): ?array
    {
        $sql_AHJR = "SELECT nombre_servicio_ahjr as nombre, costo_ahjr as monto, fecha_proximo_pago_ahjr as fecha
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid
                AND estado_ahjr = 'activa'
                AND fecha_proximo_pago_ahjr >= CURDATE()
                AND MONTH(fecha_proximo_pago_ahjr) = MONTH(CURDATE())
                AND YEAR(fecha_proximo_pago_ahjr) = YEAR(CURDATE())
                ORDER BY costo_ahjr DESC
                LIMIT 1";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute(['uid' => $uid_AHJR]);

        $result_AHJR = $stmt_AHJR->fetch();
        return $result_AHJR ?: null;
    }

    /**
     * Obtiene el total de suscripciones activas.
     */
    public function obtenerTotalSuscripciones_AHJR(int $uid_AHJR): int
    {
        $sql_AHJR = "SELECT COUNT(*) as total FROM td_suscripciones_ahjr WHERE id_usuario_suscripcion_ahjr = :uid AND estado_ahjr = 'activa'";
        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute(['uid' => $uid_AHJR]);
        $result_AHJR = $stmt_AHJR->fetch();
        return (int) ($result_AHJR['total'] ?? 0);
    }

    /**
     * Top 3 vencimientos más cercanos.
     */
    public function obtenerProximosVencimientos_AHJR(int $uid_AHJR, int $limit_AHJR = 3): array
    {
        $sql_AHJR = "SELECT id_suscripcion_ahjr as id, nombre_servicio_ahjr as nombre, fecha_proximo_pago_ahjr as fecha, costo_ahjr as monto,
                DATEDIFF(fecha_proximo_pago_ahjr, CURDATE()) as dias_restantes
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid
                AND estado_ahjr = 'activa'
                AND fecha_proximo_pago_ahjr >= CURDATE()
                ORDER BY fecha_proximo_pago_ahjr ASC
                LIMIT :limit";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->bindValue(':uid', $uid_AHJR, PDO::PARAM_INT);
        $stmt_AHJR->bindValue(':limit', $limit_AHJR, PDO::PARAM_INT);
        $stmt_AHJR->execute();

        return $stmt_AHJR->fetchAll();
    }

    /**
     * Gasto agrupado por semanas del mes actual.
     */
    public function obtenerGastoPorSemana_AHJR(int $uid_AHJR): array
    {
        // Inicializar semanas
        $semanas_AHJR = [
            'Semana 1' => 0.0,
            'Semana 2' => 0.0,
            'Semana 3' => 0.0,
            'Semana 4+' => 0.0
        ];

        // Obtener suscripciones que vencen este mes
        $sql_AHJR = "SELECT costo_ahjr, DAY(fecha_proximo_pago_ahjr) as dia
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid
                AND estado_ahjr = 'activa'
                AND MONTH(fecha_proximo_pago_ahjr) = MONTH(CURDATE())
                AND YEAR(fecha_proximo_pago_ahjr) = YEAR(CURDATE())";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute(['uid' => $uid_AHJR]);
        $pagos_AHJR = $stmt_AHJR->fetchAll();

        foreach ($pagos_AHJR as $pago_AHJR) {
            $dia_AHJR = (int) $pago_AHJR['dia'];
            $monto_AHJR = (float) $pago_AHJR['costo_ahjr'];

            if ($dia_AHJR <= 7) {
                $semanas_AHJR['Semana 1'] += $monto_AHJR;
            } elseif ($dia_AHJR <= 14) {
                $semanas_AHJR['Semana 2'] += $monto_AHJR;
            } elseif ($dia_AHJR <= 21) {
                $semanas_AHJR['Semana 3'] += $monto_AHJR;
            } else {
                $semanas_AHJR['Semana 4+'] += $monto_AHJR;
            }
        }

        return [
            'labels' => array_keys($semanas_AHJR),
            'data' => array_values($semanas_AHJR)
        ];
    }
}
