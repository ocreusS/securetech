<?php
require_once __DIR__ . '/db.php';

// Iniciamos la sesión si no está ya iniciada
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Devuelve el usuario logueado o null si no hay sesión
function usuario_actual(): ?array
{
    return $_SESSION['user'] ?? null;
}

// Si no hay sesión, redirige al login
function requiere_login(): void
{
    if (!usuario_actual()) {
        header('Location: /login.php');
        exit;
    }
}

// Si el usuario no tiene el rol correcto, devuelve 403
function requiere_rol(array $roles): void
{
    requiere_login();
    $user = usuario_actual();
    if (!in_array($user['rol'], $roles, true)) {
        http_response_code(403);
        exit('Acceso denegado');
    }
}
