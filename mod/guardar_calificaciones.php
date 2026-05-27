<?php
// mod/guardar_calificaciones.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/EvidenciaDAO.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: evidencias.php');
    exit;
}

$evidenciaId = isset($_POST['evidencia_id']) ? (int)$_POST['evidencia_id'] : 0;
$calificacionesRaw = $_POST['calificacion'] ?? [];

if ($evidenciaId <= 0) {
    header('Location: evidencias.php?error=invalid_id');
    exit;
}

$dao = new EvidenciaDAO();
$evidencia = $dao->obtenerPorId($evidenciaId);
if (!$evidencia) {
    header('Location: evidencias.php?error=not_found');
    exit;
}

// Validar permiso: instructor solo puede calificar si la ficha le pertenece
if (!esAdmin()) {
    $instructorId = $_SESSION['usuario_id'] ?? null;
    if (!$instructorId || !$dao->instructorPuedeCalificar($instructorId, $evidenciaId)) {
        header('Location: evidencias.php?error=sin_permiso');
        exit;
    }
}

// Procesar calificaciones: convertir valores vacíos a null
$calificaciones = [];
foreach ($calificacionesRaw as $aprendizId => $nota) {
    $aprendizId = (int)$aprendizId;
    if ($aprendizId <= 0) continue;
    $nota = trim($nota);
    if ($nota === '') {
        $calificaciones[$aprendizId] = null;
    } else {
        $notaNum = (float)$nota;
        $calificaciones[$aprendizId] = min(5, max(0, $notaNum));
    }
}

if ($dao->guardarCalificaciones($evidenciaId, $calificaciones)) {
    header('Location: evidencias.php?success=calificado');
} else {
    header('Location: calificar_evidencia.php?id=' . $evidenciaId . '&error=db');
}
exit;
?>