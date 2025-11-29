<?php
class Response
{
    public static function json_ahjr($data_ahjr = [], int $status_ahjr = 200): void
    {
        http_response_code($status_ahjr);
        header("Content-Type: application/json; charset=UTF-8");

        // Si el mensaje principal viene dentro de $data_ahjr["message"], muéstralo arriba
        $body_ahjr = [
            "status" => $status_ahjr,
            "success" => $status_ahjr >= 200 && $status_ahjr < 300,
        ] + $data_ahjr;

        echo json_encode($body_ahjr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // ✅ 2xx — Éxitos
    public static function ok_ahjr($data_ahjr = [])
    {
        self::json_ahjr($data_ahjr, 200);
    }
    public static function created_ahjr($msg_ahjr = "Recurso creado correctamente")
    {
        self::json_ahjr(["message" => $msg_ahjr], 201);
    }

    // ⚠️ 4xx — Errores del cliente
    public static function badRequest_ahjr($msg_ahjr = "Solicitud inválida")
    {
        self::json_ahjr(["message" => $msg_ahjr], 400);
    }
    public static function unauthorized_ahjr($msg_ahjr = "No autorizado")
    {
        self::json_ahjr(["message" => $msg_ahjr], 401);
    }
    public static function forbidden_ahjr($msg_ahjr = "Acceso denegado")
    {
        self::json_ahjr(["message" => $msg_ahjr], 403);
    }
    public static function notFound_ahjr($msg_ahjr = "Recurso no encontrado")
    {
        self::json_ahjr(["message" => $msg_ahjr], 404);
    }
    public static function conflict_ahjr($msg_ahjr = "Conflicto con los datos existentes")
    {
        self::json_ahjr(["message" => $msg_ahjr], 409);
    }

    // 💥 5xx — Errores del servidor
    public static function serverError_ahjr($msg_ahjr = "Error interno del servidor")
    {
        self::json_ahjr(["message" => $msg_ahjr], 500);
    }
}
