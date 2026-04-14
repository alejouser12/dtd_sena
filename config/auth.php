<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determinar la ruta base para redirigir al login
$base_path = '';
if (strpos($_SERVER['REQUEST_URI'], '/mod/') !== false) {
    $base_path = '../';
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . $base_path . 'login.php');
    exit;
}

// Cargar funciones de permisos
require_once __DIR__ . '/permissions.php';
?>