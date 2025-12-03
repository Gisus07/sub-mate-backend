<?php

declare(strict_types=1);

namespace App\controllers;

use App\services\HomeService;
use App\core\Response;

class HomeController
{
    private HomeService $homeService_AHJR;

    public function __construct()
    {
        $this->homeService_AHJR = new HomeService();
    }

    public function index(): void
    {
        try {
            // Authenticate user using middleware
            $middleware_AHJR = new \App\core\AuthMiddleware();
            $usuario_AHJR = $middleware_AHJR->handle_AHJR();
            $uid_AHJR = (int) $usuario_AHJR['sub'];

            // Get data from service
            $data_AHJR = $this->homeService_AHJR->obtenerDatosHome_AHJR($uid_AHJR);

            // Response::json_ahjr handles merging with standard response format
            Response::json_ahjr($data_AHJR);
        } catch (\Exception $e) {
            $status_AHJR = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status_AHJR);
        }
    }
}
