<?php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    public static function send_ahjr($to_ahjr, $subject_ahjr, $html_ahjr)
    {
        if (!class_exists(PHPMailer::class)) {
            Response::serverError_ahjr('PHPMailer no instalado');
        }
        $host_ahjr = getenv('SMTP_HOST');
        $port_ahjr = getenv('SMTP_PORT');
        $user_ahjr = getenv('SMTP_USER');
        $pass_ahjr = getenv('SMTP_PASS');
        $secure_ahjr = getenv('SMTP_SECURE') ?: 'tls';
        $from_ahjr = getenv('SMTP_FROM') ?: 'no-reply@submate.app';
        $fromName_ahjr = getenv('SMTP_FROM_NAME') ?: 'SubMate';
        if (!$host_ahjr || !$port_ahjr || !$user_ahjr || !$pass_ahjr) {
            Response::serverError_ahjr('SMTP no configurado');
        }
        $mail_ahjr = new PHPMailer(true);
        $mail_ahjr->isSMTP();
        $mail_ahjr->Host = $host_ahjr;
        $mail_ahjr->SMTPAuth = true;
        $mail_ahjr->Username = $user_ahjr;
        $mail_ahjr->Password = $pass_ahjr;
        $mail_ahjr->SMTPSecure = $secure_ahjr;
        $mail_ahjr->Port = (int)$port_ahjr;
        $mail_ahjr->CharSet = 'UTF-8';
        $mail_ahjr->setFrom($from_ahjr, $fromName_ahjr);
        $mail_ahjr->addAddress($to_ahjr);
        $mail_ahjr->isHTML(true);
        $mail_ahjr->Subject = $subject_ahjr;
        $mail_ahjr->Body = $html_ahjr;
        $mail_ahjr->AltBody = strip_tags($html_ahjr);
        return $mail_ahjr->send();
    }
}
