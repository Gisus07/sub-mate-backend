<?php

/**
 * Rutas del Módulo de Suscripciones
 * 
 * ARQUITECTURA:
 * - SuscripcionController: CRUD estándar (REST) - 5 métodos
 * - SuscripcionOperacionesController: Operaciones especiales (RPC) - 2 métodos
 */

use App\Controllers\SuscripcionController;
use App\Controllers\SuscripcionOperacionesController;

// Instanciar controladores
$suscripcionController = new SuscripcionController();
$suscripcionOperacionesController = new SuscripcionOperacionesController();

// =============================================================================
// CRUD ESTÁNDAR (SuscripcionController)
// =============================================================================

// 1. GET /api/suscripciones - Lista todas las suscripciones del usuario
$router_ahjr->add_ahjr('GET', '/api/suscripciones', function () use ($suscripcionController) {
    $suscripcionController->index();
});

// 2. POST /api/suscripciones - Crea nueva suscripción
$router_ahjr->add_ahjr('POST', '/api/suscripciones', function () use ($suscripcionController) {
    $suscripcionController->store();
});

// 3. GET /api/suscripciones/{id} - Obtiene detalle de suscripción
$router_ahjr->add_ahjr('GET', '/api/suscripciones/(\d+)', function ($id) use ($suscripcionController) {
    $suscripcionController->show((int) $id);
});

// 4. PUT /api/suscripciones/{id} - Actualiza suscripción
$router_ahjr->add_ahjr('PUT', '/api/suscripciones/(\d+)', function ($id) use ($suscripcionController) {
    $suscripcionController->update((int) $id);
});

// 5. DELETE /api/suscripciones/{id} - Elimina suscripción
$router_ahjr->add_ahjr('DELETE', '/api/suscripciones/(\d+)', function ($id) use ($suscripcionController) {
    $suscripcionController->destroy((int) $id);
});

// =============================================================================
// OPERACIONES ESPECIALES (SuscripcionOperacionesController)
// =============================================================================

// 6. PATCH /api/suscripciones/{id}/estado - Cambia estado (activa/inactiva)
$router_ahjr->add_ahjr('PATCH', '/api/suscripciones/(\d+)/estado', function ($id) use ($suscripcionOperacionesController) {
    $suscripcionOperacionesController->cambiarEstado((int) $id);
});

// 7. POST /api/suscripciones/{id}/simular-pago - Simula pago (solo beta/admin)
$router_ahjr->add_ahjr('POST', '/api/suscripciones/(\d+)/simular-pago', function ($id) use ($suscripcionOperacionesController) {
    $suscripcionOperacionesController->simularPago((int) $id);
});
