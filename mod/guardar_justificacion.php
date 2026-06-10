<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/AprendizDAO.php';
require_once __DIR__ . '/../conexion/JustificacionDAO.php';

if (!esAprendiz()) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para realizar esta acción']);
    exit;
}

$asistenciaId = isset($_POST['asistencia_id']) ? (int)$_POST['asistencia_id'] : 0;
$justificacion = isset($_POST['justificacion']) ? trim($_POST['justificacion']) : '';
$archivo = $_FILES['archivo'] ?? null;

if ($asistenciaId <= 0 || ($justificacion === '' && empty($archivo))) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$aprendizDAO = new AprendizDAO();
$aprendizId   = (int)($_SESSION['usuario_ref_id'] ?? 0);
$usuarioId    = (int)($_SESSION['usuario_id'] ?? 0);
$usuarioEmail = $_SESSION['usuario_email'] ?? '';

$aprendiz = $aprendizDAO->obtenerPorSesion($aprendizId, $usuarioId, $usuarioEmail);
if (!$aprendiz) {
    echo json_encode(['success' => false, 'message' => 'No se encontró el aprendiz asociado']);
    exit;
}

$aprendizId = $aprendiz['APRENDIZ_ID'];

$pendiente = $aprendizDAO->obtenerJustificacionPendiente($asistenciaId, $aprendizId);
if (!$pendiente) {
    echo json_encode(['success' => false, 'message' => 'No se puede justificar esta falta (ya justificada o fuera de plazo)']);
    exit;
}

$archivoRuta = null;
if ($archivo && is_uploaded_file($archivo['tmp_name'])) {
    $allowedExtensions = ['jpg','jpeg','png','gif','pdf'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Usa JPG, PNG, GIF o PDF.']);
        exit;
    }
    $uploadDir = __DIR__ . '/../uploads/justificaciones';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $filename = 'justificacion_' . $aprendizId . '_' . time() . '.' . $extension;
    $destPath = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($archivo['tmp_name'], $destPath)) {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar el archivo adjunto']);
        exit;
    }
    $archivoRuta = 'uploads/justificaciones/' . $filename;
}

$resultado = $aprendizDAO->guardarJustificacion($asistenciaId, $aprendizId, $justificacion, $archivoRuta);

if ($resultado) {
    echo json_encode(['success' => true, 'message' => 'Justificación enviada correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar la justificación']);
}
?>