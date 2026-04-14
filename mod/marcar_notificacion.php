<?php
// mod/marcar_notificacion.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/AlertaDAO.php';

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$alertaDAO = new AlertaDAO();
$resultado = $alertaDAO->marcarComoLeida($_POST['id']);

if ($resultado) {
    // Disparar evento para actualizar otros tabs/páginas
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al marcar como leída']);
}
?>