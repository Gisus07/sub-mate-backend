<?php

namespace App\core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    /**
     * Función de envío central
     * 
     * @param string $to Email destinatario
     * @param string $subject Asunto del correo
     * @param string $bodyHTML Cuerpo del correo (ya renderizado o HTML puro)
     * @return bool
     */
    public static function sendEmail(string $to, string $subject, string $bodyHTML): bool
    {
        if (!class_exists(PHPMailer::class)) {
            error_log('PHPMailer no instalado');
            return false;
        }

        $host = getenv('SMTP_HOST');
        $port = getenv('SMTP_PORT');
        $user = getenv('SMTP_USER');
        $pass = getenv('SMTP_PASS');
        $secure = getenv('SMTP_SECURE');
        $from = getenv('SMTP_FROM');
        $fromName = getenv('SMTP_FROM_NAME');

        if (!$host || !$port || !$user || !$pass) {
            error_log('SMTP no configurado');
            return false;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;
            $mail->SMTPSecure = $secure;
            $mail->Port = (int)$port;
            $mail->CharSet = 'UTF-8';

            // SMTP Debugging - Activar con SMTP_DEBUG=true en .env
            $debugMode = getenv('SMTP_DEBUG');
            if ($debugMode === 'true' || $debugMode === '1') {
                $mail->SMTPDebug = 2; // Nivel 2: Muestra comunicación cliente/servidor
                $mail->Debugoutput = 'error_log'; // Envía output a error_log de PHP
            } else {
                $mail->SMTPDebug = 0; // Modo silencioso
            }

            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHTML;
            $mail->AltBody = strip_tags($bodyHTML);

            return $mail->send();
        } catch (Exception $e) {
            error_log("Error enviando correo: {$mail->ErrorInfo}");
            return false;
        }
    }

    /**
     * Genera el template HTML responsivo con CSS en línea
     * 
     * @param string $title Título del mensaje
     * @param string $message Mensaje principal
     * @param string|null $ctaContent Texto del botón o llamada a la acción (Opcional)
     * @param string|null $ctaButtonLink Enlace del botón (Opcional)
     * @return string HTML completo
     */
    public static function generarTemplateHTML(string $title, string $message, ?string $ctaContent = null, ?string $ctaButtonLink = null): string
    {
        // Paleta de colores SubMate
        $colorPrimary = '#7C3AED';   // Morado
        $colorAccent = '#22D3EE';    // Cian
        $colorBg = '#1E1E1E';        // Fondo oscuro
        $colorCard = '#2A2A2A';      // Fondo tarjeta
        $colorText = '#FFFFFF';      // Texto claro
        $colorTextMuted = '#A1A1AA'; // Texto secundario

        $buttonHtml = '';
        if ($ctaContent) {
            if ($ctaButtonLink) {
                $buttonHtml = "
                    <table role='presentation' cellpadding='0' cellspacing='0' style='margin-top: 30px;'>
                        <tr>
                            <td align='center' style='border-radius: 8px; background: linear-gradient(90deg, {$colorPrimary} 0%, {$colorAccent} 100%);'>
                                <a href='{$ctaButtonLink}' target='_blank' style='border: 0; solid {$colorPrimary}; border-radius: 8px; color: #ffffff; display: inline-block; font-size: 16px; font-weight: bold; line-height: 50px; padding: 0 30px; text-align: center; text-decoration: none; -webkit-text-size-adjust: none;'>
                                    {$ctaContent}
                                </a>
                            </td>
                        </tr>
                    </table>
                ";
            } else {
                // Si no hay link, el CTA se muestra como texto destacado
                $buttonHtml = "
                    <div style='margin-top: 30px; padding: 15px; background-color: rgba(34, 211, 238, 0.1); border-left: 4px solid {$colorAccent}; color: {$colorAccent}; font-weight: bold;'>
                        {$ctaContent}
                    </div>
                ";
            }
        }

        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$title}</title>
            <style>
                @media only screen and (max-width: 600px) {
                    .main-table { width: 100% !important; }
                    .content-padding { padding: 20px !important; }
                }
            </style>
        </head>
        <body style='margin: 0; padding: 0; background-color: {$colorBg}; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; color: {$colorText};'>
            <table role='presentation' cellpadding='0' cellspacing='0' width='100%' style='background-color: {$colorBg};'>
                <tr>
                    <td align='center' style='padding: 40px 10px;'>
                        <!-- Header Logo -->
                        <table role='presentation' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; margin-bottom: 20px;'>
                            <tr>
                                <td align='center'>
                                    <h1 style='margin: 0; font-size: 32px; font-weight: 800; color: {$colorText}; letter-spacing: -1px;'>
                                        <span style='color: {$colorPrimary};'>Sub</span><span style='color: {$colorAccent};'>Mate</span>
                                    </h1>
                                </td>
                            </tr>
                        </table>

                        <!-- Main Card -->
                        <table class='main-table' role='presentation' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; background-color: {$colorCard}; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.3); border: 1px solid #333;'>
                            <!-- Gradient Top Border -->
                            <tr>
                                <td height='4' style='background: linear-gradient(90deg, {$colorPrimary} 0%, {$colorAccent} 100%);'></td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td class='content-padding' style='padding: 40px;'>
                                    <h2 style='margin: 0 0 20px 0; font-size: 24px; font-weight: 700; color: {$colorText}; text-align: center;'>
                                        {$title}
                                    </h2>
                                    
                                    <div style='font-size: 16px; line-height: 1.6; color: #D4D4D8; text-align: center;'>
                                        {$message}
                                    </div>

                                    <div align='center'>
                                        {$buttonHtml}
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <!-- Footer -->
                        <table role='presentation' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; margin-top: 30px;'>
                            <tr>
                                <td align='center' style='color: {$colorTextMuted}; font-size: 12px;'>
                                    <p style='margin: 0 0 10px 0;'>Controla tus suscripciones con claridad.</p>
                                    <p style='margin: 0;'>© 2025 SubMate. Todos los derechos reservados.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
    }

    /**
     * Mantiene compatibilidad para envío de OTP
     */
    public static function sendOTP_ahjr(string $to, string $otp, string $subject = "Código de Verificación")
    {
        $message = "Utiliza el siguiente código para verificar tu identidad. Este código expirará en 15 minutos.";
        $html = self::generarTemplateHTML($subject, $message, $otp, null);
        return self::sendEmail($to, $subject, $html);
    }
}
