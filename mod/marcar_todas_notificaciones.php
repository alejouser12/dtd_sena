<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/AlertaDAO.php';

$alertaDAO = new AlertaDAO();
$resultado = $alertaDAO->marcarTodasComoLeidas();

echo json_encode(['success' => $resultado]);
?>