<?php

declare(strict_types=1);

namespace App\models;

use App\core\Database;
use PDO;

/**
 * UsuarioOTPModel - Gestión de Registros Pendientes y OTP
 * 
 * Responsabilidad: Manejar tabla td_registro_pendiente_ahjr
 */
class UsuarioOTPModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getDB();
    }

    /**
     * 1. Crea registro pendiente con OTP
     */
    public function crearRegistroPendiente(array $datos): int
    {
        // Limpiar intentos previos del mismo email
        $this->eliminarPendientesPorEmail($datos['email']);

        $sql = "INSERT INTO td_registro_pendiente_ahjr 
                (nombre_ahjr, apellido_ahjr, email_ahjr, clave_ahjr, otp_hash_ahjr, otp_expira_ahjr)
                VALUES (:nombre, :apellido, :email, :clave, :otp_hash, :otp_expira)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($datos);

        return (int) $this->db->lastInsertId();
    }

    /**
     * 2. Obtiene registro pendiente por Email
     */
    public function obtenerPendientePorEmail(string $email): ?array
    {
        $sql = "SELECT * FROM td_registro_pendiente_ahjr WHERE email_ahjr = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => strtolower(trim($email))]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * 3. Elimina registro pendiente por ID
     */
    public function eliminarPendiente(int $id): bool
    {
        $sql = "DELETE FROM td_registro_pendiente_ahjr WHERE id_pendiente_ahjr = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    // ===== PASSWORD RESET =====

    public function crearResetPendiente(array $datos): int
    {
        $this->eliminarResetPorEmail($datos['email']);

        $sql = "INSERT INTO td_reset_clave_ahjr 
                (email_ahjr, otp_hash_ahjr, otp_expira_ahjr)
                VALUES (:email, :otp_hash, :otp_expira)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($datos);

        return (int) $this->db->lastInsertId();
    }

    public function obtenerResetPorEmail(string $email): ?array
    {
        $sql = "SELECT * FROM td_reset_clave_ahjr WHERE email_ahjr = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => strtolower(trim($email))]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function eliminarReset(int $id): bool
    {
        $sql = "DELETE FROM td_reset_clave_ahjr WHERE id_reset_ahjr = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    private function eliminarPendientesPorEmail(string $email): void
    {
        $sql = "DELETE FROM td_registro_pendiente_ahjr WHERE email_ahjr = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
    }

    private function eliminarResetPorEmail(string $email): void
    {
        $sql = "DELETE FROM td_reset_clave_ahjr WHERE email_ahjr = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
    }
}
