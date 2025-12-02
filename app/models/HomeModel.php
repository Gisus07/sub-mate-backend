<?php

declare(strict_types=1);

namespace App\models;

use App\core\Database;
use PDO;

class HomeModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getDB();
    }

    /**
     * Obtiene la suma de cobros en los próximos 7 días.
     */
    public function obtenerGastoProximos7Dias(int $uid): float
    {
        $sql = "SELECT SUM(costo_ahjr) as total 
                FROM td_suscripciones_ahjr 
                WHERE id_usuario_suscripcion_ahjr = :uid 
                AND estado_ahjr = 'activa'
                AND fecha_proximo_pago_ahjr BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $uid]);

        $result = $stmt->fetch();
        return (float) ($result['total'] ?? 0);
    }

    /**
     * Busca la suscripción activa con mayor costo este mes.
     */
    public function obtenerProximoGranCargo(int $uid): ?array
    {
        $sql = "SELECT nombre_servicio_ahjr as nombre, costo_ahjr as monto, fecha_proximo_pago_ahjr as fecha
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid
                AND estado_ahjr = 'activa'
                AND fecha_proximo_pago_ahjr >= CURDATE()
                AND MONTH(fecha_proximo_pago_ahjr) = MONTH(CURDATE())
                AND YEAR(fecha_proximo_pago_ahjr) = YEAR(CURDATE())
                ORDER BY costo_ahjr DESC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $uid]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Obtiene el total de suscripciones activas.
     */
    public function obtenerTotalSuscripciones(int $uid): int
    {
        $sql = "SELECT COUNT(*) as total FROM td_suscripciones_ahjr WHERE id_usuario_suscripcion_ahjr = :uid AND estado_ahjr = 'activa'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $uid]);
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Top 3 vencimientos más cercanos.
     */
    public function obtenerProximosVencimientos(int $uid, int $limit = 3): array
    {
        $sql = "SELECT id_suscripcion_ahjr as id, nombre_servicio_ahjr as nombre, fecha_proximo_pago_ahjr as fecha, costo_ahjr as monto,
                DATEDIFF(fecha_proximo_pago_ahjr, CURDATE()) as dias_restantes
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid
                AND estado_ahjr = 'activa'
                AND fecha_proximo_pago_ahjr >= CURDATE()
                ORDER BY fecha_proximo_pago_ahjr ASC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Gasto agrupado por semanas del mes actual.
     */
    public function obtenerGastoPorSemana(int $uid): array
    {
        // Inicializar semanas
        $semanas = [
            'Semana 1' => 0.0,
            'Semana 2' => 0.0,
            'Semana 3' => 0.0,
            'Semana 4+' => 0.0
        ];

        // Obtener suscripciones que vencen este mes
        $sql = "SELECT costo_ahjr, DAY(fecha_proximo_pago_ahjr) as dia
                FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid
                AND estado_ahjr = 'activa'
                AND MONTH(fecha_proximo_pago_ahjr) = MONTH(CURDATE())
                AND YEAR(fecha_proximo_pago_ahjr) = YEAR(CURDATE())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $uid]);
        $pagos = $stmt->fetchAll();

        foreach ($pagos as $pago) {
            $dia = (int) $pago['dia'];
            $monto = (float) $pago['costo_ahjr'];

            if ($dia <= 7) {
                $semanas['Semana 1'] += $monto;
            } elseif ($dia <= 14) {
                $semanas['Semana 2'] += $monto;
            } elseif ($dia <= 21) {
                $semanas['Semana 3'] += $monto;
            } else {
                $semanas['Semana 4+'] += $monto;
            }
        }

        return [
            'labels' => array_keys($semanas),
            'data' => array_values($semanas)
        ];
    }
}
