<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/app.php';

if (!isset($_SESSION['usuario_id'])) {
    redirect_to('login.php');
}

// Cargar funciones de permisos
require_once __DIR__ . '/permissions.php';
?>
