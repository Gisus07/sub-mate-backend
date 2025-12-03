<?php

declare(strict_types=1);

namespace App\services;

use App\core\Mailer;
use App\models\UsuarioModel;
use Exception;

class AlertsService
{
    private UsuarioModel $usuarioModel_AHJR;
    private string $appUrl_AHJR;

    public function __construct()
    {
        $this->usuarioModel_AHJR = new UsuarioModel();
        $this->appUrl_AHJR = getenv('APP_URL') ?: 'http://localhost:3000'; // Fallback a local
    }

    /**
     * Envía correo de bienvenida tras registro exitoso
     */
    public function enviarRegistroExitoso_AHJR(array $usuario_AHJR): bool
    {
        $email_AHJR = $usuario_AHJR['email_ahjr'] ?? $usuario_AHJR['email'];
        $nombre_AHJR = $usuario_AHJR['nombre_ahjr'] ?? $usuario_AHJR['nombre'];

        $title_AHJR = "¡Bienvenido a SubMate, {$nombre_AHJR}!";
        $message_AHJR = "Gracias por unirte a SubMate. Ahora tienes el control total de tus suscripciones en un solo lugar. Empieza registrando tu primera suscripción.";
        // Sin CTA

        $html_AHJR = Mailer::generarTemplateHTML_AHJR($title_AHJR, $message_AHJR);

        try {
            return Mailer::sendEmail_AHJR($email_AHJR, "Bienvenido a SubMate", $html_AHJR);
        } catch (Exception $e) {
            error_log("AlertsService::enviarRegistroExitoso_AHJR - Fallo al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica nueva suscripción creada
     */
    public function enviarSuscripcionCreada_AHJR(array $suscripcion_AHJR): bool
    {
        $usuario_AHJR = $this->obtenerUsuario_AHJR($suscripcion_AHJR);
        if (!$usuario_AHJR) return false;

        $nombreServicio_AHJR = $suscripcion_AHJR['nombre_servicio'] ?? $suscripcion_AHJR['nombre_servicio_ahjr'];

        $title_AHJR = "Nueva Suscripción: {$nombreServicio_AHJR}";
        $message_AHJR = "Has registrado correctamente una nueva suscripción. Te avisaremos antes de tu próximo pago.";

        $html_AHJR = Mailer::generarTemplateHTML_AHJR($title_AHJR, $message_AHJR);

        try {
            return Mailer::sendEmail_AHJR($usuario_AHJR['email_ahjr'], "Nueva Suscripción Registrada", $html_AHJR);
        } catch (Exception $e) {
            error_log("AlertsService::enviarSuscripcionCreada_AHJR - Fallo al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica edición de suscripción
     */
    public function enviarSuscripcionEditada_AHJR(array $suscripcion_AHJR): bool
    {
        $usuario_AHJR = $this->obtenerUsuario_AHJR($suscripcion_AHJR);
        if (!$usuario_AHJR) return false;

        $nombreServicio_AHJR = $suscripcion_AHJR['nombre_servicio'] ?? $suscripcion_AHJR['nombre_servicio_ahjr'];

        $title_AHJR = "Suscripción Actualizada";
        $message_AHJR = "Los detalles de tu suscripción a <strong>{$nombreServicio_AHJR}</strong> han sido modificados exitosamente.";

        $html_AHJR = Mailer::generarTemplateHTML_AHJR($title_AHJR, $message_AHJR);

        try {
            return Mailer::sendEmail_AHJR($usuario_AHJR['email_ahjr'], "Actualización de Suscripción - {$nombreServicio_AHJR}", $html_AHJR);
        } catch (Exception $e) {
            error_log("AlertsService::enviarSuscripcionEditada_AHJR - Fallo al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica desactivación de suscripción
     */
    public function enviarSuscripcionDesactivada_AHJR(array $suscripcion_AHJR): bool
    {
        $usuario_AHJR = $this->obtenerUsuario_AHJR($suscripcion_AHJR);
        if (!$usuario_AHJR) return false;

        $nombreServicio_AHJR = $suscripcion_AHJR['nombre_servicio'] ?? $suscripcion_AHJR['nombre_servicio_ahjr'];

        $title_AHJR = "Suscripción Pausada";
        $message_AHJR = "Has desactivado los recordatorios para <strong>{$nombreServicio_AHJR}</strong>. No recibirás más alertas de pago para este servicio hasta que lo reactives.";

        $html_AHJR = Mailer::generarTemplateHTML_AHJR($title_AHJR, $message_AHJR);

        try {
            return Mailer::sendEmail_AHJR($usuario_AHJR['email_ahjr'], "Suscripción Desactivada - {$nombreServicio_AHJR}", $html_AHJR);
        } catch (Exception $e) {
            error_log("AlertsService::enviarSuscripcionDesactivada_AHJR - Fallo al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía recordatorio de pago (Worker)
     */
    public function enviarRecordatorio_AHJR(array $suscripcion_AHJR, int $diasRestantes_AHJR): bool
    {
        $usuario_AHJR = $this->obtenerUsuario_AHJR($suscripcion_AHJR);
        if (!$usuario_AHJR) return false;

        $nombreServicio_AHJR = $suscripcion_AHJR['nombre_servicio'] ?? $suscripcion_AHJR['nombre_servicio_ahjr'];
        $costo_AHJR = $suscripcion_AHJR['costo'] ?? $suscripcion_AHJR['costo_ahjr'];
        // Asumimos moneda base si no está en array, o formateamos
        $monto_AHJR = number_format((float)$costo_AHJR, 2);

        $urgencia_AHJR = $diasRestantes_AHJR <= 3 ? "¡Atención!" : "Recordatorio";
        $title_AHJR = "{$urgencia_AHJR} Pago próximo de {$nombreServicio_AHJR}";

        $message_AHJR = "Tu pago de <strong>\${$monto_AHJR}</strong> para <strong>{$nombreServicio_AHJR}</strong> vence en <strong>{$diasRestantes_AHJR} días</strong>. Asegúrate de tener fondos disponibles.";

        $html_AHJR = Mailer::generarTemplateHTML_AHJR($title_AHJR, $message_AHJR);

        try {
            return Mailer::sendEmail_AHJR($usuario_AHJR['email_ahjr'], "Recordatorio de Pago: {$nombreServicio_AHJR}", $html_AHJR);
        } catch (Exception $e) {
            error_log("AlertsService::enviarRecordatorio_AHJR - Fallo al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica eliminación de suscripción
     */
    public function enviarSuscripcionEliminada_AHJR(array $suscripcion_AHJR): bool
    {
        $usuario_AHJR = $this->obtenerUsuario_AHJR($suscripcion_AHJR);
        if (!$usuario_AHJR) return false;

        $nombreServicio_AHJR = $suscripcion_AHJR['nombre_servicio'] ?? $suscripcion_AHJR['nombre_servicio_ahjr'];

        $title_AHJR = "Suscripción Eliminada";
        $message_AHJR = "Has eliminado permanentemente la suscripción a <strong>{$nombreServicio_AHJR}</strong>. Ya no recibirás notificaciones sobre este servicio.";

        $html_AHJR = Mailer::generarTemplateHTML_AHJR($title_AHJR, $message_AHJR);

        try {
            return Mailer::sendEmail_AHJR($usuario_AHJR['email_ahjr'], "Suscripción Eliminada - {$nombreServicio_AHJR}", $html_AHJR);
        } catch (Exception $e) {
            error_log("AlertsService::enviarSuscripcionEliminada_AHJR - Fallo al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica reactivación de suscripción
     */
    public function enviarSuscripcionActivada_AHJR(array $suscripcion_AHJR): bool
    {
        $usuario_AHJR = $this->obtenerUsuario_AHJR($suscripcion_AHJR);
        if (!$usuario_AHJR) return false;

        $nombreServicio_AHJR = $suscripcion_AHJR['nombre_servicio'] ?? $suscripcion_AHJR['nombre_servicio_ahjr'];

        $title_AHJR = "Suscripción Reactivada";
        $message_AHJR = "¡Excelente! Has reactivado los recordatorios para <strong>{$nombreServicio_AHJR}</strong>. Te avisaremos antes de tu próximo pago.";

        $html_AHJR = Mailer::generarTemplateHTML_AHJR($title_AHJR, $message_AHJR);

        try {
            return Mailer::sendEmail_AHJR($usuario_AHJR['email_ahjr'], "Suscripción Reactivada - {$nombreServicio_AHJR}", $html_AHJR);
        } catch (Exception $e) {
            error_log("AlertsService::enviarSuscripcionActivada_AHJR - Fallo al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía confirmación de contacto recibido
     */
    public function enviarConfirmacionContacto_AHJR(array $datosContacto_AHJR): bool
    {
        $nombre_AHJR = $datosContacto_AHJR['nombre_completo_ahjr'];
        $email_AHJR = $datosContacto_AHJR['email_ahjr'];
        $asunto_AHJR = $datosContacto_AHJR['asunto_ahjr'];

        // Formatear asunto para el correo
        $asuntoCapitalizado_AHJR = ucfirst($asunto_AHJR);
        $title_AHJR = "¡Gracias por escribirnos, {$nombre_AHJR}!";
        $message_AHJR = "Hemos recibido tu <strong>{$asuntoCapitalizado_AHJR}</strong> correctamente. Nuestro equipo revisará tu mensaje y te responderá a la brevedad posible.";

        $html_AHJR = Mailer::generarTemplateHTML_AHJR($title_AHJR, $message_AHJR);

        try {
            return Mailer::sendEmail_AHJR($email_AHJR, "Recibimos tu {$asuntoCapitalizado_AHJR} - SubMate", $html_AHJR);
        } catch (Exception $e) {
            error_log("AlertsService::enviarConfirmacionContacto_AHJR - Fallo al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    // --- Helper ---
    private function obtenerUsuario_AHJR(array $suscripcion_AHJR): ?array
    {
        // INTENTO 1: id_usuario (DTO limpio)
        if (isset($suscripcion_AHJR['id_usuario'])) {
            return $this->usuarioModel_AHJR->obtenerPorId_AHJR((int)$suscripcion_AHJR['id_usuario']);
        }

        // INTENTO 2: id_usuario_suscripcion_ahjr (BD cruda)
        if (isset($suscripcion_AHJR['id_usuario_suscripcion_ahjr'])) {
            return $this->usuarioModel_AHJR->obtenerPorId_AHJR((int)$suscripcion_AHJR['id_usuario_suscripcion_ahjr']);
        }

        // INTENTO 3: id_usuario_ahjr (Worker map)
        if (isset($suscripcion_AHJR['id_usuario_ahjr'])) {
            return $this->usuarioModel_AHJR->obtenerPorId_AHJR((int)$suscripcion_AHJR['id_usuario_ahjr']);
        }

        return null;
    }
}
