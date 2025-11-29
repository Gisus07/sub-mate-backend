<?php
require_once __DIR__ . '/../controllers/UsuarioController.php';
require_once __DIR__ . '/../controllers/SuscripcionController.php';

$controller_ahjr = new UsuarioController($db_ahjr);
$susController_ahjr = new SuscripcionController($db_ahjr);

// POST /auth/register
$router_ahjr->add_ahjr('POST', '/auth/register', function () use ($controller_ahjr) {
    $controller_ahjr->registrar_ahjr();
});

// POST /auth/login
$router_ahjr->add_ahjr('POST', '/auth/login', function () use ($controller_ahjr) {
    $controller_ahjr->login_ahjr();
});

// Alias
$router_ahjr->add_ahjr('POST', '/api/auth/login', function () use ($controller_ahjr) {
    $controller_ahjr->login_ahjr();
});

// GET /auth/email-available
$router_ahjr->add_ahjr('GET', '/auth/email-available', function () use ($controller_ahjr) {
    $controller_ahjr->validarCorreo_ahjr();
});

// Listar todos los usuarios (Admin)
$router_ahjr->add_ahjr('GET', '/auth/usuario', function () use ($controller_ahjr) {
    $controller_ahjr->listarUsuarios_ahjr();
});

// Obtener perfil
$router_ahjr->add_ahjr('GET', '/auth/usuario/(\d+)', function ($id_ahjr) use ($controller_ahjr) {
    $controller_ahjr->obtenerPerfil_ahjr($id_ahjr);
});

// Editar perfil
$router_ahjr->add_ahjr('PUT', '/auth/usuario/(\d+)', function ($id_ahjr) use ($controller_ahjr) {
    $controller_ahjr->editarPerfil_ahjr($id_ahjr);
});

// Eliminar cuenta
$router_ahjr->add_ahjr('DELETE', '/auth/usuario/(\d+)', function ($id_ahjr) use ($controller_ahjr) {
    $controller_ahjr->eliminarCuenta_ahjr($id_ahjr);
});
// POST /auth/logout
$router_ahjr->add_ahjr('POST', '/auth/logout', function () use ($controller_ahjr) {
    $controller_ahjr->logout_ahjr();
});

// Cambiar rol (admin)
$router_ahjr->add_ahjr('PUT', '/auth/usuario/(\d+)/rol', function ($id_ahjr) use ($controller_ahjr) {
    $controller_ahjr->cambiarRol_ahjr($id_ahjr);
});

$router_ahjr->add_ahjr('GET', '/auth/usuario/(\d+)/suscripciones', function ($uid_ahjr) use ($susController_ahjr) {
    $susController_ahjr->listar_ahjr($uid_ahjr);
});
$router_ahjr->add_ahjr('POST', '/auth/usuario/(\d+)/suscripciones', function ($uid_ahjr) use ($susController_ahjr) {
    $susController_ahjr->crear_ahjr($uid_ahjr);
});
$router_ahjr->add_ahjr('GET', '/auth/usuario/(\d+)/suscripciones/(\d+)', function ($uid_ahjr, $id_ahjr) use ($susController_ahjr) {
    $susController_ahjr->obtener_ahjr($uid_ahjr, $id_ahjr);
});
$router_ahjr->add_ahjr('PUT', '/auth/usuario/(\d+)/suscripciones/(\d+)', function ($uid_ahjr, $id_ahjr) use ($susController_ahjr) {
    $susController_ahjr->editar_ahjr($uid_ahjr, $id_ahjr);
});
$router_ahjr->add_ahjr('DELETE', '/auth/usuario/(\d+)/suscripciones/(\d+)', function ($uid_ahjr, $id_ahjr) use ($susController_ahjr) {
    $susController_ahjr->eliminar_ahjr($uid_ahjr, $id_ahjr);
});
$router_ahjr->add_ahjr('POST', '/auth/register-verify', function () use ($controller_ahjr) {
    $controller_ahjr->verificarOtp_ahjr();
});

$router_ahjr->add_ahjr('POST', '/auth/register-resend', function () use ($controller_ahjr) {
    $controller_ahjr->reenviarOtp_ahjr();
});
$router_ahjr->add_ahjr('GET', '/auth/session', function () use ($controller_ahjr) {
    $controller_ahjr->session_ahjr();
});

$router_ahjr->add_ahjr('POST', '/auth/password-reset', function () use ($controller_ahjr) {
    $controller_ahjr->solicitarReset_ahjr();
});

$router_ahjr->add_ahjr('POST', '/auth/password-reset-verify', function () use ($controller_ahjr) {
    $controller_ahjr->verificarReset_ahjr();
});
