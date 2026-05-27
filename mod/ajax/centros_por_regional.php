<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../conexion/CentroDAO.php';

$regionalId = isset($_GET['regional_id']) ? (int)$_GET['regional_id'] : 0;
if ($regionalId <= 0) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}
$centroDAO = new CentroDAO();
$centros = $centroDAO->obtenerPorRegional($regionalId);
echo json_encode($centros);
?>