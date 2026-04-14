<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/AlertaDAO.php';

$alertaDAO = new AlertaDAO();
$alertas = $alertaDAO->obtenerAlertasActivas();

echo json_encode($alertas);
?>