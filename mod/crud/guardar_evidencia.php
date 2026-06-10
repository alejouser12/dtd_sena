<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';

// Instructores y admin pueden guardar evidencias
if (!esAdmin() && !esInstructor()) {
    header('Location: ../evidencias.php');
    exit;
}

require_once __DIR__ . '/../../conexion/EvidenciaDAO.php';
require_once __DIR__ . '/../../conexion/instructorDAO.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    header('Location: ../evidencias.php'); 
    exit; 
}

$fichaId = (int)($_POST['ficha_id'] ?? 0);

// Si es instructor, validar que la ficha le pertenece
if (!esAdmin()) {
    $instructorId = (int)($_SESSION['usuario_ref_id'] ?? 0);
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
    
    if ($instructorId > 0) {
        $instDAO = new InstructorDAO();
        $fichasAsignadas = $instDAO->obtenerFichasIds($instructorId);
        if (!in_array($fichaId, $fichasAsignadas)) {
            header('Location: ../evidencias.php?error=No tienes permiso para crear evidencias en esa ficha');
            exit;
        }
    } else {
        header('Location: ../evidencias.php?error=No se pudo identificar tu ID de instructor');
        exit;
    }
}

$dao = new EvidenciaDAO();
if (!empty($_POST['evidencias_id'])) {
    $dao->actualizarEvidencia((int)$_POST['evidencias_id'], $_POST['nombre'], $_POST['tipo_evidencia'], (float)$_POST['porcentaje'], $_POST['fecha_evidencia'], $_POST['tiempo_entrega'], (int)$_POST['ficha_id'], $_POST['estado_evidencia'] ?? 'activa');
} else {
    $dao->crearEvidencia($_POST['nombre'], $_POST['tipo_evidencia'], (float)$_POST['porcentaje'], $_POST['fecha_evidencia'], $_POST['tiempo_entrega'], (int)$_POST['ficha_id'], $_POST['estado_evidencia'] ?? 'activa');
}
header('Location: ../evidencias.php');
exit;
