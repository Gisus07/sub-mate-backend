<?php

declare(strict_types=1);

namespace App\controllers;

use App\core\Response;
use App\core\Mailer;
use App\core\Env;

/**
 * DebugController
 * 
 * Controlador para pruebas y debugging del sistema.
 * ADVERTENCIA: Solo para uso en desarrollo/staging.
 */
class DebugController
{
    /**
     * POST /api/debug/test-email
     * 
     * Envía un correo de prueba para verificar configuración SMTP.
     * 
     * Body esperado:
     * {
     *   "email": "destinatario@ejemplo.com"
     * }
     * 
     * @return void
     */
    public function testEmail(): void
    {
        // Leer JSON del body
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['email']) || empty($input['email'])) {
            Response::badRequest_ahjr('El campo "email" es requerido');
            return;
        }

        $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            Response::badRequest_ahjr('El email proporcionado no es válido');
            return;
        }

        // Generar correo de prueba
        $title = "🔧 Email de Prueba - SubMate";
        $message = "Este es un correo de prueba para verificar la configuración SMTP de SubMate. Si recibes este mensaje, significa que la configuración está funcionando correctamente.";

        $html = Mailer::generarTemplateHTML_AHJR($title, $message);

        // Log del intento
        error_log("DebugController::testEmail - Intentando enviar correo de prueba a: {$email}");

        // Intentar enviar
        try {
            $resultado = Mailer::sendEmail_AHJR($email, "Prueba SMTP - SubMate", $html);

            if ($resultado) {
                error_log("DebugController::testEmail - Correo enviado exitosamente a: {$email}");
                Response::ok_ahjr([
                    'message' => 'Correo de prueba enviado exitosamente',
                    'email' => $email,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                error_log("DebugController::testEmail - Fallo al enviar correo a: {$email}");
                $debug = Env::get('SMTP_DEBUG');
                $detail = Mailer::getLastError();
                if ($debug === 'true' || $debug === '1') {
                    Response::json_ahjr([
                        'message' => 'No se pudo enviar el correo',
                        'email' => $email,
                        'error' => $detail ?? 'sin detalle',
                        'timestamp' => date('Y-m-d H:i:s')
                    ], 500);
                } else {
                    Response::serverError_ahjr('No se pudo enviar el correo. Revisa los logs del servidor para más detalles.');
                }
            }
        } catch (\Exception $e) {
            error_log("DebugController::testEmail - Excepción al enviar correo: " . $e->getMessage());
            Response::serverError_ahjr('Error al procesar el envío: ' . $e->getMessage());
        }
    }

    public function smtpConfig(): void
    {
        $host = Env::get('SMTP_HOST') ?? Env::get('MAIL_HOST');
        $port = Env::get('SMTP_PORT') ?? Env::get('MAIL_PORT');
        $secure = Env::get('SMTP_SECURE') ?? Env::get('MAIL_ENCRYPTION');
        $from = Env::get('SMTP_FROM') ?? Env::get('MAIL_FROM');
        $fromName = Env::get('SMTP_FROM_NAME') ?? Env::get('MAIL_FROM_NAME');
        $debug = Env::get('SMTP_DEBUG');
        $skipVerify = Env::get('SMTP_SKIP_TLS_VERIFY');
        $timeout = Env::get('SMTP_TIMEOUT');

        Response::ok_ahjr([
            'smtp' => [
                'host' => $host,
                'port' => $port,
                'secure' => $secure,
                'from' => $from,
                'from_name' => $fromName,
                'debug' => $debug,
                'skip_tls_verify' => $skipVerify,
                'timeout' => $timeout,
            ],
        ]);
    }
}
