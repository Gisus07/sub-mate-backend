<?php

namespace App\services;

use App\models\ContactoModel;
use App\services\AlertsService;
use Exception;

class ContactoService
{
    private ContactoModel $contactoModel_AHJR;
    private AlertsService $alertsService_AHJR;

    public function __construct()
    {
        $this->contactoModel_AHJR = new ContactoModel();
        $this->alertsService_AHJR = new AlertsService();
    }

    public function procesarContacto_AHJR(array $datosLimpios_AHJR): bool
    {
        // 1. Mapear datos a formato de base de datos (_ahjr)
        $datosBD_AHJR = [
            'nombre_completo_ahjr' => $datosLimpios_AHJR['nombre'],
            'email_ahjr' => $datosLimpios_AHJR['email'],
            'telefono_ahjr' => $datosLimpios_AHJR['telefono'] ?? null,
            'asunto_ahjr' => $datosLimpios_AHJR['asunto'],
            'mensaje_ahjr' => $datosLimpios_AHJR['mensaje']
        ];

        // 2. Insertar en base de datos
        $idContacto_AHJR = $this->contactoModel_AHJR->crear_AHJR($datosBD_AHJR);

        if (!$idContacto_AHJR) {
            throw new Exception("Error al guardar el mensaje de contacto.");
        }

        // 3. Enviar correo de confirmación
        // No bloqueamos el flujo si falla el correo, pero lo logueamos en el servicio de alertas
        $this->alertsService_AHJR->enviarConfirmacionContacto_AHJR($datosBD_AHJR);

        return true;
    }
}
