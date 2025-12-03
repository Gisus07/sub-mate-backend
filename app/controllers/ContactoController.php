<?php

namespace App\controllers;

use App\core\Response;
use App\services\ContactoService;
use Exception;

class ContactoController
{
    private ContactoService $contactoService_AHJR;

    public function __construct()
    {
        $this->contactoService_AHJR = new ContactoService();
    }

    public function enviar(): void
    {
        // 1. Obtener datos del POST
        $data_AHJR = json_decode(file_get_contents('php://input'), true);

        // 2. Validar campos obligatorios
        if (empty($data_AHJR['nombre']) || empty($data_AHJR['email']) || empty($data_AHJR['asunto']) || empty($data_AHJR['mensaje'])) {
            Response::json_ahjr(['message' => "Todos los campos son obligatorios (nombre, email, asunto, mensaje)."], 400);
            return;
        }

        // 3. Validar formato de email
        if (!filter_var($data_AHJR['email'], FILTER_VALIDATE_EMAIL)) {
            Response::json_ahjr(['message' => "El formato del correo electrónico no es válido."], 400);
            return;
        }

        // 4. Validar asunto permitido
        $asuntosPermitidos_AHJR = ['consulta', 'propuesta', 'soporte'];
        if (!in_array($data_AHJR['asunto'], $asuntosPermitidos_AHJR)) {
            Response::json_ahjr(['message' => "El asunto seleccionado no es válido."], 400);
            return;
        }

        try {
            // 5. Procesar contacto
            $this->contactoService_AHJR->procesarContacto_AHJR($data_AHJR);
            Response::json_ahjr(['message' => "Mensaje enviado con éxito. Te responderemos pronto."], 200);
        } catch (Exception $e) {
            error_log("ContactoController::enviar - Error: " . $e->getMessage());
            Response::json_ahjr(['message' => "Ocurrió un error al enviar tu mensaje. Por favor, intenta nuevamente."], 500);
        }
    }
}
