<?php

declare(strict_types=1);

namespace App\models;

use App\core\Database;
use PDO;

/**
 * UsuarioModel - Acceso a Datos
 * 
 * RESTRICCIÓN: Exactamente 6 métodos públicos
 * Responsabilidad: SOLO SQL con columnas _ahjr
 */
class UsuarioModel
{
    private PDO $db_AHJR;

    public function __construct()
    {
        $this->db_AHJR = Database::getDB_AHJR();
    }

    /**
     * 1. Crea un nuevo usuario en td_usuarios_ahjr
     */
    public function crear_AHJR(array $datos_AHJR): int
    {
        $sql_AHJR = "INSERT INTO td_usuarios_ahjr 
                (nombre_ahjr, apellido_ahjr, email_ahjr, clave_ahjr, estado_ahjr, rol_ahjr)
                VALUES (:nombre, :apellido, :email, :clave, :estado, :rol)";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute($datos_AHJR);

        return (int) $this->db_AHJR->lastInsertId();
    }

    /**
     * 2. Busca usuario por email_ahjr
     */
    public function buscarPorEmail_AHJR(string $email_AHJR): ?array
    {
        $sql_AHJR = "SELECT * FROM td_usuarios_ahjr WHERE email_ahjr = :email LIMIT 1";
        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute(['email' => strtolower(trim($email_AHJR))]);

        $result_AHJR = $stmt_AHJR->fetch();
        return $result_AHJR ?: null;
    }

    /**
     * 3. Obtiene usuario por id_ahjr
     */
    public function obtenerPorId_AHJR(int $id_AHJR): ?array
    {
        $sql_AHJR = "SELECT * FROM td_usuarios_ahjr WHERE id_ahjr = :id LIMIT 1";
        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute(['id' => $id_AHJR]);

        $result_AHJR = $stmt_AHJR->fetch();
        return $result_AHJR ?: null;
    }

    /**
     * 4. Actualiza datos del usuario
     */
    public function actualizar_AHJR(int $id_AHJR, array $datos_AHJR): bool
    {
        $campos_AHJR = [];
        $params_AHJR = ['id' => $id_AHJR];

        foreach ($datos_AHJR as $clave_AHJR => $valor_AHJR) {
            $campos_AHJR[] = "{$clave_AHJR} = :{$clave_AHJR}";
            $params_AHJR[$clave_AHJR] = $valor_AHJR;
        }

        if (empty($campos_AHJR)) {
            return false;
        }

        $sql_AHJR = "UPDATE td_usuarios_ahjr SET " . implode(', ', $campos_AHJR) . " WHERE id_ahjr = :id";
        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);

        return $stmt_AHJR->execute($params_AHJR);
    }

    /**
     * 5. Elimina usuario por id_ahjr
     */
    public function eliminar_AHJR(int $id_AHJR): bool
    {
        $sql_AHJR = "DELETE FROM td_usuarios_ahjr WHERE id_ahjr = :id";
        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);

        return $stmt_AHJR->execute(['id' => $id_AHJR]);
    }

    /**
     * 6. Verifica si un email ya está registrado
     */
    public function existeEmail_AHJR(string $email_AHJR): bool
    {
        $sql_AHJR = "SELECT COUNT(*) FROM td_usuarios_ahjr WHERE email_ahjr = :email";
        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute(['email' => strtolower(trim($email_AHJR))]);

        return (int) $stmt_AHJR->fetchColumn() > 0;
    }
}
