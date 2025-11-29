<?php

/**
 * Rutas del Dashboard
 */

use App\Controllers\DashboardController;

$dashboardController = new DashboardController();

// GET /api/dashboard - Obtener todos los datos del dashboard
$router_ahjr->add_ahjr('GET', '/api/dashboard', [$dashboardController, 'index']);
