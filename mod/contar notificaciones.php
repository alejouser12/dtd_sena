<?php
// mod/contar_notificaciones.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/AlertaDAO.php';

$alertaDAO = new AlertaDAO();
$conteo = $alertaDAO->obtenerConteoAlertas();

echo json_encode($conteo);
?>