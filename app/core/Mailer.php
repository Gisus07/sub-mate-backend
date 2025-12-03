<?php

namespace App\core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\core\Env;

class Mailer
{
    private static ?string $lastError = null;

    public static function getLastError(): ?string
    {
        return self::$lastError;
    }
    /**
     * Función de envío central
     * 
     * @param string $to_AHJR Email destinatario
     * @param string $subject_AHJR Asunto del correo
     * @param string $bodyHTML_AHJR Cuerpo del correo (ya renderizado o HTML puro)
     * @return bool
     */
    public static function sendEmail_AHJR(string $to_AHJR, string $subject_AHJR, string $bodyHTML_AHJR): bool
    {
        if (!class_exists(PHPMailer::class)) {
            self::$lastError = 'PHPMailer no instalado';
            error_log('PHPMailer no instalado');
            return false;
        }

        if (!extension_loaded('openssl')) {
            self::$lastError = 'Extensión openssl no cargada';
            error_log('Extensión openssl no cargada');
            return false;
        }

        $host = Env::get('SMTP_HOST') ?? Env::get('MAIL_HOST');
        $port = Env::get('SMTP_PORT') ?? Env::get('MAIL_PORT');
        $user = Env::get('SMTP_USER') ?? Env::get('MAIL_USERNAME');
        $pass = Env::get('SMTP_PASS') ?? Env::get('MAIL_PASSWORD');
        $secure = Env::get('SMTP_SECURE') ?? Env::get('MAIL_ENCRYPTION');
        $from = Env::get('SMTP_FROM') ?? Env::get('MAIL_FROM') ?? $user;
        $fromName = Env::get('SMTP_FROM_NAME') ?? Env::get('MAIL_FROM_NAME') ?? 'SubMate';

        if (!$host || !$port || !$user || !$pass) {
            $faltantes = [];
            if (!$host) { $faltantes[] = 'HOST'; }
            if (!$port) { $faltantes[] = 'PORT'; }
            if (!$user) { $faltantes[] = 'USER'; }
            if (!$pass) { $faltantes[] = 'PASS'; }
            self::$lastError = 'SMTP no configurado. Faltan: ' . implode(', ', $faltantes);
            error_log('SMTP no configurado. Faltan: ' . implode(', ', $faltantes));
            return false;
        }

        if (!$secure) {
            if ((int)$port === 587) {
                $secure = 'tls';
            } elseif ((int)$port === 465) {
                $secure = 'ssl';
            }
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $forceIPv4 = Env::get('SMTP_FORCE_IPV4');
            $mail->Host = ($forceIPv4 === 'true' || $forceIPv4 === '1') ? gethostbyname($host) : $host;
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;
            $mail->SMTPSecure = $secure;
            $mail->Port = (int)$port;
            $mail->SMTPAutoTLS = true;
            $mail->CharSet = 'UTF-8';
            $timeout = Env::get('SMTP_TIMEOUT');
            if ($timeout) {
                $mail->Timeout = (int)$timeout;
            }

            $skipVerify = Env::get('SMTP_SKIP_TLS_VERIFY');
            if ($skipVerify === 'true' || $skipVerify === '1') {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];
            }

            // SMTP Debugging - Activar con SMTP_DEBUG=true en .env
            $debugMode = getenv('SMTP_DEBUG');
            if ($debugMode === 'true' || $debugMode === '1') {
                $mail->SMTPDebug = 2; // Nivel 2: Muestra comunicación cliente/servidor
                $mail->Debugoutput = 'error_log'; // Envía output a error_log de PHP
            } else {
                $mail->SMTPDebug = 0; // Modo silencioso
            }

            $mail->setFrom($from, $fromName);
            $mail->addAddress($to_AHJR);

            $mail->isHTML(true);
            $mail->Subject = $subject_AHJR;
            $mail->Body = $bodyHTML_AHJR;
            $mail->AltBody = strip_tags($bodyHTML_AHJR);

            $result = $mail->send();
            if (!$result) {
                self::$lastError = $mail->ErrorInfo;
            } else {
                self::$lastError = null;
            }
            return $result;
        } catch (Exception $e) {
            self::$lastError = isset($mail) ? $mail->ErrorInfo : $e->getMessage();
            error_log("Error enviando correo: " . (self::$lastError ?? 'sin detalle'));
            return false;
        }
    }

    /**
     * Genera el template HTML responsivo con CSS en línea
     * 
     * @param string $title_AHJR Título del mensaje
     * @param string $message_AHJR Mensaje principal
     * @param string|null $ctaContent_AHJR Texto del botón o llamada a la acción (Opcional)
     * @param string|null $ctaButtonLink_AHJR Enlace del botón (Opcional)
     * @return string HTML completo
     */
    public static function generarTemplateHTML_AHJR(string $title_AHJR, string $message_AHJR, ?string $ctaContent_AHJR = null, ?string $ctaButtonLink_AHJR = null): string
    {
        // Paleta de colores SubMate
        $colorPrimary = '#7C3AED';   // Morado
        $colorAccent = '#22D3EE';    // Cian
        $colorBg = '#1E1E1E';        // Fondo oscuro
        $colorCard = '#2A2A2A';      // Fondo tarjeta
        $colorText = '#FFFFFF';      // Texto claro
        $colorTextMuted = '#A1A1AA'; // Texto secundario

        $buttonHtml_AHJR = '';
        if ($ctaContent_AHJR) {
            if ($ctaButtonLink_AHJR) {
                $buttonHtml_AHJR = "
                    <table role='presentation' cellpadding='0' cellspacing='0' style='margin-top: 30px;'>
                        <tr>
                            <td align='center' style='border-radius: 8px; background: linear-gradient(90deg, {$colorPrimary} 0%, {$colorAccent} 100%);'>
                                <a href='{$ctaButtonLink_AHJR}' target='_blank' style='border: 0; solid {$colorPrimary}; border-radius: 8px; color: #ffffff; display: inline-block; font-size: 16px; font-weight: bold; line-height: 50px; padding: 0 30px; text-align: center; text-decoration: none; -webkit-text-size-adjust: none;'>
                                    {$ctaContent_AHJR}
                                </a>
                            </td>
                        </tr>
                    </table>
                ";
            } else {
                // Si no hay link, el CTA se muestra como texto destacado
                $buttonHtml_AHJR = "
                    <div style='margin-top: 30px; padding: 15px; background-color: rgba(34, 211, 238, 0.1); border-left: 4px solid {$colorAccent}; color: {$colorAccent}; font-weight: bold;'>
                        {$ctaContent_AHJR}
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
            <title>{$title_AHJR}</title>
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
                                        {$title_AHJR}
                                    </h2>
                                    
                                    <div style='font-size: 16px; line-height: 1.6; color: #D4D4D8; text-align: center;'>
                                        {$message_AHJR}
                                    </div>

                                    <div align='center'>
                                        {$buttonHtml_AHJR}
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
    public static function sendOTP_ahjr(string $to_AHJR, string $otp_AHJR, string $subject_AHJR = "Código de Verificación")
    {
        $message_AHJR = "Utiliza el siguiente código para verificar tu identidad. Este código expirará en 15 minutos.";
        $html_AHJR = self::generarTemplateHTML_AHJR($subject_AHJR, $message_AHJR, $otp_AHJR, null);
        return self::sendEmail_AHJR($to_AHJR, $subject_AHJR, $html_AHJR);
    }
}
