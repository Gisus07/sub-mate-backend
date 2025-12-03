<?php

/**
 * API Routes - Centralized Routing
 * 
 * Todas las rutas del sistema consolidadas en un solo archivo.
 * Los controladores manejan la autenticación internamente.
 */

use App\controllers\AuthController;
use App\controllers\UsuarioController;
use App\controllers\SuscripcionController;
use App\controllers\SuscripcionOperacionesController;
use App\controllers\DashboardController;
use App\controllers\HomeController;
use App\controllers\ContactoController;
use App\controllers\DebugController;
use App\core\Response;

// =============================================================================
// INSTANCIAR CONTROLADORES
// =============================================================================

$authController = new AuthController();
$usuarioController = new UsuarioController();
$suscripcionController = new SuscripcionController();
$operacionesController = new SuscripcionOperacionesController();
$dashboardController = new DashboardController();
$homeController = new HomeController();
$contactoController = new ContactoController();
$debugController = new DebugController();

// =============================================================================
// RUTA RAÍZ - Documentación de API
// =============================================================================

$router_ahjr->add_ahjr('GET', '/', function () {
    Response::ok_ahjr([
        "message" => "Bienvenido al backend de SubMate 🚀",
        "version" => "2.0",
        "endpoints" => [
            "Auth" => [
                "POST /api/auth/register" => "Registrar nuevo usuario",
                "POST /api/auth/register-verify" => "Verificar código OTP de registro",
                "POST /api/auth/register-resend" => "Reenviar código OTP",
                "POST /api/auth/login" => "Iniciar sesión y obtener token JWT",
                "POST /api/auth/logout" => "Cerrar sesión",
                "GET /api/auth/me" => "Obtener usuario autenticado desde token",
                "GET /api/auth/email-available" => "Verificar disponibilidad de email (UX Check)",
                "POST /api/auth/password-reset" => "Solicitar reset de contraseña",
                "POST /api/auth/password-reset-verify" => "Verificar código y cambiar contraseña"
            ],
            "Usuario" => [
                "PUT /api/perfil" => "Actualizar perfil del usuario autenticado",
                "PATCH /api/perfil/password" => "Cambiar contraseña",
                "DELETE /api/perfil" => "Eliminar cuenta del usuario autenticado"
            ],
            "Suscripciones" => [
                "GET /api/suscripciones" => "Listar suscripciones del usuario",
                "POST /api/suscripciones" => "Crear nueva suscripción",
                "GET /api/suscripciones/{id}" => "Obtener detalle de suscripción",
                "PUT /api/suscripciones/{id}" => "Actualizar suscripción",
                "DELETE /api/suscripciones/{id}" => "Eliminar suscripción",
                "PATCH /api/suscripciones/{id}/estado" => "Cambiar estado (activa/inactiva)",
                "POST /api/suscripciones/{id}/simular-pago" => "Simular pago (solo beta/admin)"
            ],
            "Dashboard" => [
                "GET /api/dashboard" => "Obtener datos analíticos y gráficas",
                "GET /api/home" => "Obtener datos para la vista principal (Home)"
            ]
        ]
    ]);
});

// =============================================================================
// AUTH MODULE - Autenticación y Registro
// =============================================================================

// POST /api/auth/register - Registro de nuevo usuario
$router_ahjr->add_ahjr('POST', '/api/auth/register', function () use ($authController) {
    $authController->register_ahjr();
});

// POST /api/auth/login - Inicio de sesión
$router_ahjr->add_ahjr('POST', '/api/auth/login', function () use ($authController) {
    $authController->login_ahjr();
});

// Alias para compatibilidad con rutas antiguas
$router_ahjr->add_ahjr('POST', '/auth/login', function () use ($authController) {
    $authController->login_ahjr();
});

// GET /api/auth/me - Obtener usuario autenticado
$router_ahjr->add_ahjr('GET', '/api/auth/me', function () use ($authController) {
    $authController->me_ahjr();
});

// POST /api/auth/logout - Cerrar sesión
$router_ahjr->add_ahjr('POST', '/api/auth/logout', function () use ($authController) {
    $authController->logout_ahjr();
});

// POST /api/auth/register-verify - Verificar código OTP de registro
$router_ahjr->add_ahjr('POST', '/api/auth/register-verify', function () use ($authController) {
    $authController->verifyOTP_ahjr();
});

// POST /api/auth/password-reset - Solicitar reset de contraseña
$router_ahjr->add_ahjr('POST', '/api/auth/password-reset', function () use ($authController) {
    $authController->passwordReset_ahjr();
});

// POST /api/auth/password-reset-verify - Verificar código y cambiar contraseña
$router_ahjr->add_ahjr('POST', '/api/auth/password-reset-verify', function () use ($authController) {
    $authController->passwordResetVerify_ahjr();
});

// POST /auth/password-reset - Solicitar reset de contraseña (legacy)
$router_ahjr->add_ahjr('POST', '/auth/password-reset', function () use ($authController) {
    $authController->passwordReset_ahjr();
});

// POST /auth/password-reset-verify - Verificar código y cambiar contraseña (legacy)
$router_ahjr->add_ahjr('POST', '/auth/password-reset-verify', function () use ($authController) {
    $authController->passwordResetVerify_ahjr();
});

// GET /auth/session - Verificar sesión activa (legacy)
$router_ahjr->add_ahjr('GET', '/auth/session', function () use ($authController) {
    $authController->me_ahjr();
});

// GET /api/auth/email-available - Validar disponibilidad de correo
$router_ahjr->add_ahjr('GET', '/api/auth/email-available', function () use ($authController) {
    $authController->checkEmailAvailability_ahjr();
});

// GET /auth/email-available - Validar disponibilidad de correo (legacy)
$router_ahjr->add_ahjr('GET', '/auth/email-available', function () use ($authController) {
    $authController->checkEmailAvailability_ahjr();
});

// =============================================================================
// USUARIO MODULE - Gestión de Perfil
// =============================================================================

// PUT /api/perfil - Actualizar perfil del usuario autenticado
$router_ahjr->add_ahjr('PUT', '/api/perfil', function () use ($usuarioController) {
    $usuarioController->update();
});

// PATCH /api/perfil/password - Cambiar contraseña del usuario autenticado
$router_ahjr->add_ahjr('PATCH', '/api/perfil/password', function () use ($usuarioController) {
    $usuarioController->updatePassword();
});

// DELETE /api/perfil - Eliminar cuenta del usuario autenticado
$router_ahjr->add_ahjr('DELETE', '/api/perfil', function () use ($usuarioController) {
    $usuarioController->delete();
});

// =============================================================================
// SUSCRIPCIONES MODULE - CRUD Estándar
// =============================================================================

// GET /api/suscripciones - Listar suscripciones del usuario
$router_ahjr->add_ahjr('GET', '/api/suscripciones', function () use ($suscripcionController) {
    $suscripcionController->index();
});

// POST /api/suscripciones - Crear nueva suscripción
$router_ahjr->add_ahjr('POST', '/api/suscripciones', function () use ($suscripcionController) {
    $suscripcionController->store();
});

// GET /api/suscripciones/{id} - Obtener detalle de suscripción
$router_ahjr->add_ahjr('GET', '/api/suscripciones/(\d+)', function ($id) use ($suscripcionController) {
    $suscripcionController->show((int) $id);
});

// PUT /api/suscripciones/{id} - Actualizar suscripción
$router_ahjr->add_ahjr('PUT', '/api/suscripciones/(\d+)', function ($id) use ($suscripcionController) {
    $suscripcionController->update((int) $id);
});

// DELETE /api/suscripciones/{id} - Eliminar suscripción
$router_ahjr->add_ahjr('DELETE', '/api/suscripciones/(\d+)', function ($id) use ($suscripcionController) {
    $suscripcionController->destroy((int) $id);
});

// =============================================================================
// SUSCRIPCIONES OPERACIONES - Operaciones Especiales
// =============================================================================

// PATCH /api/suscripciones/{id}/estado - Cambiar estado (activa/inactiva)
$router_ahjr->add_ahjr('PATCH', '/api/suscripciones/(\d+)/estado', function ($id) use ($operacionesController) {
    $operacionesController->cambiarEstado((int) $id);
});

// POST /api/suscripciones/{id}/simular-pago - Simular pago (solo beta/admin)
$router_ahjr->add_ahjr('POST', '/api/suscripciones/(\d+)/simular-pago', function ($id) use ($operacionesController) {
    $operacionesController->simularPago((int) $id);
});

// =============================================================================
// DASHBOARD MODULE - Analytics
// =============================================================================

// GET /api/dashboard - Obtener datos analíticos para gráficas
$router_ahjr->add_ahjr('GET', '/api/dashboard', function () use ($dashboardController) {
    $dashboardController->index();
});

// GET /api/home - Obtener datos para la vista principal
$router_ahjr->add_ahjr('GET', '/api/home', function () use ($homeController) {
    $homeController->index();
});

// =============================================================================
// CONTACTO MODULE - Formulario Público
// =============================================================================

// POST /api/contacto - Enviar mensaje de contacto
$router_ahjr->add_ahjr('POST', '/api/contacto', function () use ($contactoController) {
    $contactoController->enviar();
});

// =============================================================================
// LEGACY ROUTES - Compatibilidad con rutas antiguas
// =============================================================================

// Rutas de usuario legacy (mantener por compatibilidad)
$router_ahjr->add_ahjr('GET', '/auth/usuario', function () {
    Response::ok_ahjr(['message' => 'Ruta legacy - usar /api/perfil']);
});

$router_ahjr->add_ahjr('GET', '/auth/usuario/(\d+)', function ($id) {
    Response::ok_ahjr(['message' => 'Ruta legacy - usar /api/perfil']);
});

$router_ahjr->add_ahjr('PUT', '/auth/usuario/(\d+)', function ($id) {
    Response::ok_ahjr(['message' => 'Ruta legacy - usar /api/perfil']);
});

$router_ahjr->add_ahjr('DELETE', '/auth/usuario/(\d+)', function ($id) {
    Response::ok_ahjr(['message' => 'Ruta legacy - usar /api/perfil']);
});

$router_ahjr->add_ahjr('PUT', '/auth/usuario/(\d+)/rol', function ($id) {
    Response::ok_ahjr(['message' => 'Ruta legacy - funcionalidad movida']);
});

// =============================================================================
// DEBUG ROUTES - Solo para desarrollo/demo
// =============================================================================

// POST /api/debug/run-worker - Ejecutar worker manualmente
$router_ahjr->add_ahjr('POST', '/api/debug/run-worker', function () {
    // Verificar que sea admin o entorno local (opcional, por ahora abierto para demo)
    // Ejecutar el script worker.php y capturar salida
    $output = [];
    $returnVar = 0;
    exec('php ' . __DIR__ . '/../../scripts/worker.php', $output, $returnVar);

    Response::ok_ahjr([
        'message' => 'Worker ejecutado',
        'output' => $output,
        'exit_code' => $returnVar
    ]);
});

// POST /api/debug/test-email - Enviar correo de prueba SMTP
$router_ahjr->add_ahjr('POST', '/api/debug/test-email', function () use ($debugController) {
    $debugController->testEmail();
});

// GET /api/debug/smtp-config - Ver configuración SMTP actual (sin secretos)
$router_ahjr->add_ahjr('GET', '/api/debug/smtp-config', function () use ($debugController) {
    $debugController->smtpConfig();
});
