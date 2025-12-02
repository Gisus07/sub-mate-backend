<?php

declare(strict_types=1);

namespace App\controllers;

use App\services\HomeService;
use App\core\Response;

class HomeController
{
    private HomeService $homeService;

    public function __construct()
    {
        $this->homeService = new HomeService();
    }

    public function index(): void
    {
        try {
            // Authenticate user using middleware
            $middleware = new \App\core\AuthMiddleware();
            $usuario = $middleware->handle();
            $uid = (int) $usuario['sub'];

            // Get data from service
            $data = $this->homeService->obtenerDatosHome($uid);

            // Response::json_ahjr handles merging with standard response format
            Response::json_ahjr($data);
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json_ahjr(['message' => $e->getMessage()], $status);
        }
    }
}
