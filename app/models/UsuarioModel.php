<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * UsuarioModel - Acceso a Datos
 * 
 * RESTRICCIÓN: Exactamente 6 métodos públicos
 * Responsabilidad: SOLO SQL con columnas _ahjr
 */
class UsuarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getDB();
    }

    /**
     * 1. Crea un nuevo usuario en td_usuarios_ahjr
     */
    public function crear(array $datos): int
    {
        $sql = "INSERT INTO td_usuarios_ahjr 
                (nombre_ahjr, apellido_ahjr, email_ahjr, clave_ahjr, estado_ahjr, rol_ahjr)
                VALUES (:nombre, :apellido, :email, :clave, :estado, :rol)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($datos);

        return (int) $this->db->lastInsertId();
    }

    /**
     * 2. Busca usuario por email_ahjr
     */
    public function buscarPorEmail(string $email): ?array
    {
        $sql = "SELECT * FROM td_usuarios_ahjr WHERE email_ahjr = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => strtolower(trim($email))]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * 3. Obtiene usuario por id_ahjr
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT * FROM td_usuarios_ahjr WHERE id_ahjr = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * 4. Actualiza datos del usuario
     */
    public function actualizar(int $id, array $datos): bool
    {
        $campos = [];
        $params = ['id' => $id];

        foreach ($datos as $clave => $valor) {
            $campos[] = "{$clave} = :{$clave}";
            $params[$clave] = $valor;
        }

        if (empty($campos)) {
            return false;
        }

        $sql = "UPDATE td_usuarios_ahjr SET " . implode(', ', $campos) . " WHERE id_ahjr = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * 5. Elimina usuario por id_ahjr
     */
    public function eliminar(int $id): bool
    {
        $sql = "DELETE FROM td_usuarios_ahjr WHERE id_ahjr = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(['id' => $id]);
    }

    /**
     * 6. Verifica si un email ya está registrado
     */
    public function existeEmail(string $email): bool
    {
        $sql = "SELECT COUNT(*) FROM td_usuarios_ahjr WHERE email_ahjr = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => strtolower(trim($email))]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
