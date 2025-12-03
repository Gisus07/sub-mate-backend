<?php

namespace App\models;

use App\core\Database;
use PDO;

class ContactoModel
{
    private $db_AHJR;

    public function __construct()
    {
        $this->db_AHJR = Database::getDB_AHJR();
    }

    public function crear_AHJR(array $datos_AHJR): int
    {
        $sql_AHJR = "INSERT INTO td_contacto_ahjr (nombre_completo_ahjr, email_ahjr, telefono_ahjr, asunto_ahjr, mensaje_ahjr) 
                VALUES (:nombre, :email, :telefono, :asunto, :mensaje)";

        $stmt_AHJR = $this->db_AHJR->prepare($sql_AHJR);
        $stmt_AHJR->execute([
            ':nombre' => $datos_AHJR['nombre_completo_ahjr'],
            ':email' => $datos_AHJR['email_ahjr'],
            ':telefono' => $datos_AHJR['telefono_ahjr'] ?? null,
            ':asunto' => $datos_AHJR['asunto_ahjr'],
            ':mensaje' => $datos_AHJR['mensaje_ahjr']
        ]);

        return (int) $this->db_AHJR->lastInsertId();
    }
}
