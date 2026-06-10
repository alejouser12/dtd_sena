<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/JustificacionDAO.php';
require_once __DIR__ . '/../conexion/InstructorDAO.php';

if (!esInstructor() && !esAdmin()) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para realizar esta acción']);
    exit;
}

$justificacionId = isset($_POST['justificacion_id']) ? (int)$_POST['justificacion_id'] : 0;
$estado = trim($_POST['estado'] ?? '');
$comentario = trim($_POST['comentario'] ?? '');

if ($justificacionId <= 0 || !in_array($estado, ['aprobada', 'rechazada'], true)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$instructorId = esInstructor() ? (int)($_SESSION['usuario_ref_id'] ?? 0) : (int)($_POST['instructor_id'] ?? 0);
if ($instructorId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Instructor no identificado']);
    exit;
}

$instructorDAO = new InstructorDAO();
$justificacionDAO = new JustificacionDAO();
$justificacion = $justificacionDAO->obtenerPorId($justificacionId);
if (!$justificacion) {
    echo json_encode(['success' => false, 'message' => 'Justificación no encontrada']);
    exit;
}

// Validar que el instructor sea responsable por la ficha del aprendiz
$fichas = $instructorDAO->obtenerFichasIds($instructorId);
if (!in_array((int)$justificacion['FICHA_ID'], $fichas, true)) {
    echo json_encode(['success' => false, 'message' => 'No estás autorizado para revisar esta justificación']);
    exit;
}

$ok = $justificacionDAO->actualizarEstado($justificacionId, $estado, $instructorId, $comentario ?: null);
if ($ok) {
    echo json_encode(['success' => true, 'message' => 'La justificación se ha actualizado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la justificación']);
}
