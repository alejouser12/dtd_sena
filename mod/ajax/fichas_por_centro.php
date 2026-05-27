<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../conexion/FichaDAO.php';

$centroId = isset($_GET['centro_id']) ? (int)$_GET['centro_id'] : 0;
if ($centroId <= 0) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}
$fichaDAO = new FichaDAO();
$fichas = $fichaDAO->obtenerPorCentro($centroId);
echo json_encode($fichas);
?>