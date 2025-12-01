<?php

declare(strict_types=1);

namespace App\services;

use App\core\Mailer;
use App\models\UsuarioModel;
use Exception;

class AlertsService
{
    private UsuarioModel $usuarioModel;
    private string $appUrl;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
        $this->appUrl = getenv('APP_URL') ?: 'http://localhost:3000'; // Fallback a local
    }

    /**
     * Envía correo de bienvenida tras registro exitoso
     */
    public function enviarRegistroExitoso(array $usuario): bool
    {
        $email = $usuario['email_ahjr'] ?? $usuario['email'];
        $nombre = $usuario['nombre_ahjr'] ?? $usuario['nombre'];

        $title = "¡Bienvenido a SubMate, {$nombre}!";
        $message = "Gracias por unirte a SubMate. Ahora tienes el control total de tus suscripciones en un solo lugar. Empieza registrando tu primera suscripción.";
        // Sin CTA

        $html = Mailer::generarTemplateHTML($title, $message);

        try {
            return Mailer::sendEmail($email, "Bienvenido a SubMate", $html);
        } catch (Exception $e) {
            error_log("AlertsService::enviarRegistroExitoso - Fallo al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica nueva suscripción creada
     */
    public function enviarSuscripcionCreada(array $suscripcion): bool
    {
        $usuario = $this->obtenerUsuario($suscripcion);
        if (!$usuario) return false;

        $nombreServicio = $suscripcion['nombre_servicio'] ?? $suscripcion['nombre_servicio_ahjr'];

        $title = "Nueva Suscripción: {$nombreServicio}";
        $message = "Has registrado correctamente una nueva suscripción. Te avisaremos antes de tu próximo pago.";

        $html = Mailer::generarTemplateHTML($title, $message);

        try {
            return Mailer::sendEmail($usuario['email_ahjr'], "Nueva Suscripción Registrada", $html);
        } catch (Exception $e) {
            error_log("AlertsService::enviarSuscripcionCreada - Fallo al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica edición de suscripción
     */
    public function enviarSuscripcionEditada(array $suscripcion): bool
    {
        $usuario = $this->obtenerUsuario($suscripcion);
        if (!$usuario) return false;

        $nombreServicio = $suscripcion['nombre_servicio'] ?? $suscripcion['nombre_servicio_ahjr'];

        $title = "Suscripción Actualizada";
        $message = "Los detalles de tu suscripción a <strong>{$nombreServicio}</strong> han sido modificados exitosamente.";

        $html = Mailer::generarTemplateHTML($title, $message);

        try {
            return Mailer::sendEmail($usuario['email_ahjr'], "Actualización de Suscripción - {$nombreServicio}", $html);
        } catch (Exception $e) {
            error_log("AlertsService::enviarSuscripcionEditada - Fallo al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica desactivación de suscripción
     */
    public function enviarSuscripcionDesactivada(array $suscripcion): bool
    {
        $usuario = $this->obtenerUsuario($suscripcion);
        if (!$usuario) return false;

        $nombreServicio = $suscripcion['nombre_servicio'] ?? $suscripcion['nombre_servicio_ahjr'];

        $title = "Suscripción Pausada";
        $message = "Has desactivado los recordatorios para <strong>{$nombreServicio}</strong>. No recibirás más alertas de pago para este servicio hasta que lo reactives.";

        $html = Mailer::generarTemplateHTML($title, $message);

        try {
            return Mailer::sendEmail($usuario['email_ahjr'], "Suscripción Desactivada - {$nombreServicio}", $html);
        } catch (Exception $e) {
            error_log("AlertsService::enviarSuscripcionDesactivada - Fallo al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía recordatorio de pago (Worker)
     */
    public function enviarRecordatorio(array $suscripcion, int $diasRestantes): bool
    {
        $usuario = $this->obtenerUsuario($suscripcion);
        if (!$usuario) return false;

        $nombreServicio = $suscripcion['nombre_servicio'] ?? $suscripcion['nombre_servicio_ahjr'];
        $costo = $suscripcion['costo'] ?? $suscripcion['costo_ahjr'];
        // Asumimos moneda base si no está en array, o formateamos
        $monto = number_format((float)$costo, 2);

        $urgencia = $diasRestantes <= 3 ? "¡Atención!" : "Recordatorio";
        $title = "{$urgencia} Pago próximo de {$nombreServicio}";

        $message = "Tu pago de <strong>\${$monto}</strong> para <strong>{$nombreServicio}</strong> vence en <strong>{$diasRestantes} días</strong>. Asegúrate de tener fondos disponibles.";

        $html = Mailer::generarTemplateHTML($title, $message);

        try {
            return Mailer::sendEmail($usuario['email_ahjr'], "Recordatorio de Pago: {$nombreServicio}", $html);
        } catch (Exception $e) {
            error_log("AlertsService::enviarRecordatorio - Fallo al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica eliminación de suscripción
     */
    public function enviarSuscripcionEliminada(array $suscripcion): bool
    {
        $usuario = $this->obtenerUsuario($suscripcion);
        if (!$usuario) return false;

        $nombreServicio = $suscripcion['nombre_servicio'] ?? $suscripcion['nombre_servicio_ahjr'];

        $title = "Suscripción Eliminada";
        $message = "Has eliminado permanentemente la suscripción a <strong>{$nombreServicio}</strong>. Ya no recibirás notificaciones sobre este servicio.";

        $html = Mailer::generarTemplateHTML($title, $message);

        try {
            return Mailer::sendEmail($usuario['email_ahjr'], "Suscripción Eliminada - {$nombreServicio}", $html);
        } catch (Exception $e) {
            error_log("AlertsService::enviarSuscripcionEliminada - Fallo al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica reactivación de suscripción
     */
    public function enviarSuscripcionActivada(array $suscripcion): bool
    {
        $usuario = $this->obtenerUsuario($suscripcion);
        if (!$usuario) return false;

        $nombreServicio = $suscripcion['nombre_servicio'] ?? $suscripcion['nombre_servicio_ahjr'];

        $title = "Suscripción Reactivada";
        $message = "¡Excelente! Has reactivado los recordatorios para <strong>{$nombreServicio}</strong>. Te avisaremos antes de tu próximo pago.";

        $html = Mailer::generarTemplateHTML($title, $message);

        try {
            return Mailer::sendEmail($usuario['email_ahjr'], "Suscripción Reactivada - {$nombreServicio}", $html);
        } catch (Exception $e) {
            error_log("AlertsService::enviarSuscripcionActivada - Fallo al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    // --- Helper ---
    private function obtenerUsuario(array $suscripcion): ?array
    {
        // INTENTO 1: id_usuario (DTO limpio)
        if (isset($suscripcion['id_usuario'])) {
            return $this->usuarioModel->obtenerPorId((int)$suscripcion['id_usuario']);
        }

        // INTENTO 2: id_usuario_suscripcion_ahjr (BD cruda)
        if (isset($suscripcion['id_usuario_suscripcion_ahjr'])) {
            return $this->usuarioModel->obtenerPorId((int)$suscripcion['id_usuario_suscripcion_ahjr']);
        }

        // INTENTO 3: id_usuario_ahjr (Worker map)
        if (isset($suscripcion['id_usuario_ahjr'])) {
            return $this->usuarioModel->obtenerPorId((int)$suscripcion['id_usuario_ahjr']);
        }

        return null;
    }
}
