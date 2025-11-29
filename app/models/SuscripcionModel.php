<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * SuscripcionModel - CRUD Básico
 * 
 * RESTRICCIÓN: Exactamente 5 métodos públicos
 */
class SuscripcionModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getDB();
    }

    /**
     * 1. Crea suscripción usando SP
     */
    public function crear(array $datos): int
    {
        $sql = "CALL sp_crear_suscripcion_ahjr(?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $datos['id_usuario'],
            $datos['nombre_servicio'],
            $datos['costo'],
            $datos['frecuencia'],
            $datos['metodo_pago'],
            $datos['dia_cobro'],
            $datos['mes_cobro']
        ]);

        $result = $stmt->fetch();
        return (int) $result['id_suscripcion_ahjr'];
    }

    /**
     * 2. Lista suscripciones por usuario
     */
    public function listarPorUsuario(int $uid): array
    {
        $sql = "SELECT * FROM td_suscripciones_ahjr 
                WHERE id_usuario_suscripcion_ahjr = :uid 
                ORDER BY fecha_creacion_ahjr DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $uid]);

        return $stmt->fetchAll();
    }

    /**
     * 3. Obtiene suscripción específica
     */
    public function obtener(int $id, int $uid): ?array
    {
        $sql = "SELECT * FROM td_suscripciones_ahjr 
                WHERE id_suscripcion_ahjr = :id 
                AND id_usuario_suscripcion_ahjr = :uid 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'uid' => $uid]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * 4. Edita suscripción
     */
    public function editar(int $id, array $datos): bool
    {
        $campos = [];
        $params = ['id' => $id];

        foreach ($datos as $clave => $valor) {
            $campos[] = "{$clave} = :{$clave}";
            $params[$clave] = $valor;
        }

        if (empty($campos)) return false;

        $sql = "UPDATE td_suscripciones_ahjr SET " . implode(', ', $campos) . " WHERE id_suscripcion_ahjr = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * 5. Elimina suscripción
     */
    public function eliminar(int $id): bool
    {
        $sql = "DELETE FROM td_suscripciones_ahjr WHERE id_suscripcion_ahjr = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(['id' => $id]);
    }
}
