<?php

declare(strict_types=1);

namespace App\models;

use App\core\Database;
use PDO;

/**
 * SuscripcionModel - CRUD Básico
 * 
 * RESTRICCIÓN: Exactamente 5 métodos públicos
 */
class SuscripcionModel
{
    private PDO $db_AHJR;

    public function __construct()
    {
        $this->db_AHJR = Database::getDB_AHJR();
    }

    /**
     * 1. Crea suscripción usando SP
     */
    public function crear_AHJR(array $datos_AHJR): int
    {
        $sql_AHJR = "CALL sp_crear_suscripcion_ahjr(?, ?, ?, ?, ?, ?, ?)";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute([
            $datos_AHJR['id_usuario'],
            $datos_AHJR['nombre_servicio'],
            $datos_AHJR['costo'],
            $datos_AHJR['frecuencia'],
            $datos_AHJR['metodo_pago'],
            $datos_AHJR['dia_cobro'],
            $datos_AHJR['mes_cobro']
        ]);

        $result_AHJR = $stmt_AHJR->fetch();
        return (int) $result_AHJR['id_suscripcion_ahjr'];
    }

    /**
     * 2. Lista suscripciones por usuario
     */
    public function listarPorUsuario_AHJR(int $uid_AHJR): array
    {
        $sql_AHJR = "SELECT *, DATEDIFF(fecha_proximo_pago_ahjr, CURDATE()) as dias_restantes_ahjr 
                FROM td_suscripciones_ahjr 
                WHERE id_usuario_suscripcion_ahjr = :uid 
                ORDER BY fecha_creacion_ahjr DESC";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute(['uid' => $uid_AHJR]);

        return $stmt_AHJR->fetchAll();
    }

    /**
     * 3. Obtiene suscripción específica
     */
    public function obtener_AHJR(int $id_AHJR, int $uid_AHJR): ?array
    {
        $sql_AHJR = "SELECT *, DATEDIFF(fecha_proximo_pago_ahjr, CURDATE()) as dias_restantes_ahjr 
                FROM td_suscripciones_ahjr 
                WHERE id_suscripcion_ahjr = :id 
                AND id_usuario_suscripcion_ahjr = :uid 
                LIMIT 1";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute(['id' => $id_AHJR, 'uid' => $uid_AHJR]);

        $result_AHJR = $stmt_AHJR->fetch();
        return $result_AHJR ?: null;
    }

    /**
     * 4. Edita suscripción
     */
    public function editar_AHJR(int $id_AHJR, array $datos_AHJR): bool
    {
        $campos_AHJR = [];
        $params_AHJR = ['id' => $id_AHJR];

        foreach ($datos_AHJR as $clave_AHJR => $valor_AHJR) {
            $campos_AHJR[] = "{$clave_AHJR} = :{$clave_AHJR}";
            $params_AHJR[$clave_AHJR] = $valor_AHJR;
        }

        if (empty($campos_AHJR)) return false;

        $sql_AHJR = "UPDATE td_suscripciones_ahjr SET " . implode(', ', $campos_AHJR) . " WHERE id_suscripcion_ahjr = :id";
        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);

        return $stmt_AHJR->execute($params_AHJR);
    }

    /**
     * 5. Elimina suscripción
     */
    public function eliminar_AHJR(int $id_AHJR): bool
    {
        $sql_AHJR = "DELETE FROM td_suscripciones_ahjr WHERE id_suscripcion_ahjr = :id";
        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);

        return $stmt_AHJR->execute(['id' => $id_AHJR]);
    }
    /**
     * 6. Busca suscripción por nombre normalizado
     */
    public function buscarSuscripcionPorNombre_AHJR(int $uid_AHJR, string $nombreNormalizado_AHJR): ?array
    {
        // Normaliza el nombre en la BD: quita espacios y convierte a minúsculas
        $sql_AHJR = "SELECT * FROM td_suscripciones_ahjr 
                WHERE id_usuario_suscripcion_ahjr = :uid 
                AND LOWER(REPLACE(nombre_servicio_ahjr, ' ', '')) = :nombre
                LIMIT 1";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute(['uid' => $uid_AHJR, 'nombre' => $nombreNormalizado_AHJR]);

        $result_AHJR = $stmt_AHJR->fetch();
        return $result_AHJR ?: null;
    }
}
