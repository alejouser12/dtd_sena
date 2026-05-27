<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/AprendizDAO.php';

if (!esAprendiz()) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para realizar esta acción']);
    exit;
}

$asistenciaId = isset($_POST['asistencia_id']) ? (int)$_POST['asistencia_id'] : 0;
$justificacion = isset($_POST['justificacion']) ? trim($_POST['justificacion']) : '';

if ($asistenciaId <= 0 || empty($justificacion)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$usuarioId = $_SESSION['usuario_id'] ?? 0;

$sql = "SELECT APRENDIZ_ID FROM aprendiz WHERE usuario_id = :usuario_id LIMIT 1";
$stmt = (new AprendizDAO())->ejecutarPreparado($sql, [':usuario_id' => $usuarioId]);
$aprendiz = $stmt ? $stmt->fetch() : null;

if (!$aprendiz) {
    echo json_encode(['success' => false, 'message' => 'No se encontró el aprendiz asociado']);
    exit;
}

$aprendizId = $aprendiz['APRENDIZ_ID'];

$aprendizDAO = new AprendizDAO();
$pendiente = $aprendizDAO->obtenerJustificacionPendiente($asistenciaId, $aprendizId);

if (!$pendiente) {
    echo json_encode(['success' => false, 'message' => 'No se puede justificar esta falta (ya justificada o fuera de plazo)']);
    exit;
}

$resultado = $aprendizDAO->guardarJustificacion($asistenciaId, $aprendizId, $justificacion);

if ($resultado) {
    echo json_encode(['success' => true, 'message' => 'Justificación enviada correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar la justificación']);
}
?>