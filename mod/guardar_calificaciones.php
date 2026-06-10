<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/EvidenciaDAO.php';
require_once __DIR__ . '/../conexion/instructorDAO.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: evidencias.php');
    exit;
}

$evidenciaId = isset($_POST['evidencia_id']) ? (int)$_POST['evidencia_id'] : 0;
$calificacionesRaw = $_POST['calificacion'] ?? [];

if ($evidenciaId <= 0) {
    header('Location: evidencias.php?error=ID inválido');
    exit;
}

$dao = new EvidenciaDAO();
$evidencia = $dao->obtenerPorId($evidenciaId);
if (!$evidencia) {
    header('Location: evidencias.php?error=Evidencia no encontrada');
    exit;
}

// Verificar permiso para instructor
if (!esAdmin()) {
    if (!esInstructor()) {
        header('Location: evidencias.php?error=No tienes permiso');
        exit;
    }
    
    $instructorId = (int)($_SESSION['usuario_ref_id'] ?? 0);
    
    // Si no tiene usuario_ref_id, buscar por email
    if ($instructorId <= 0) {
        $email = $_SESSION['usuario_email'] ?? '';
        if (!empty($email)) {
            $instDAO = new InstructorDAO();
            $c = $instDAO->buscarPorColumna('EMAIL', $email);
            if (!empty($c) && isset($c[0]['INSTRUCTOR_ID'])) {
                $instructorId = (int)$c[0]['INSTRUCTOR_ID'];
            }
        }
    }

    if ($instructorId <= 0) {
        header('Location: evidencias.php?error=No se pudo identificar tu ID de instructor');
        exit;
    }

    $fichaId = $evidencia['ficha_id'];
    // Verificar que la ficha está asignada al instructor
    $instDAO = new InstructorDAO();
    $fichasAsignadas = $instDAO->obtenerFichasIds($instructorId);
    
    if (!in_array($fichaId, $fichasAsignadas)) {
        header('Location: evidencias.php?error=Esta evidencia no pertenece a ninguna de tus fichas');
        exit;
    }
}

// Procesar calificaciones
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

$resultado = $dao->guardarCalificaciones($evidenciaId, $calificaciones);
if ($resultado) {
    header('Location: evidencias.php?success=calificado');
} else {
    header('Location: calificar_evidencia.php?id=' . $evidenciaId . '&error=db');
}
exit;
?>