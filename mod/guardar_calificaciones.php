<?php
// mod/guardar_calificaciones.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/EvidenciaDAO.php';

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: evidencias.php');
    exit;
}

// Validar evidencia_id
$evidencia_id = intval($_POST['evidencia_id'] ?? 0);
if ($evidencia_id <= 0) {
    header('Location: evidencias.php');
    exit;
}

// Obtener arrays de estados y calificaciones
$estados = $_POST['estado'] ?? [];
$calificaciones_numeros = $_POST['calificacion'] ?? [];

// Construir array de calificaciones a guardar
$calificaciones = [];
foreach ($estados as $aprendiz_id => $estado) {
    $aprendiz_id = intval($aprendiz_id);
    if ($aprendiz_id > 0 && !empty($estado)) { // Solo si se seleccionó un estado
        $calificaciones[$aprendiz_id] = [
            'estado' => $estado,
            'calificacion' => isset($calificaciones_numeros[$aprendiz_id]) ? floatval($calificaciones_numeros[$aprendiz_id]) : null
        ];
    }
}

// Si no hay calificaciones, mostrar error
if (empty($calificaciones)) {
    echo '<!DOCTYPE html><html><head><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script></head><body>';
    echo '<script>
        Swal.fire({
            icon: "warning",
            title: "Sin datos",
            text: "No se seleccionó ningún estado para calificar.",
            confirmButtonText: "Aceptar"
        }).then(() => { window.location.href = "calificar_evidencia.php?id=' . $evidencia_id . '"; });
    </script></body></html>';
    exit;
}

// Guardar
$dao = new EvidenciaDAO();
$resultado = $dao->guardarCalificaciones($evidencia_id, $calificaciones);

if ($resultado) {
    echo '<!DOCTYPE html><html><head><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script></head><body>';
    echo '<script>
        Swal.fire({
            icon: "success",
            title: "Calificaciones guardadas",
            text: "Las calificaciones se han registrado correctamente.",
            timer: 2000,
            showConfirmButton: false
        }).then(() => { window.location.href = "evidencias.php"; });
    </script></body></html>';
} else {
    echo '<!DOCTYPE html><html><head><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script></head><body>';
    echo '<script>
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "No se pudieron guardar las calificaciones: ' . addslashes($dao->imprimirError()) . '",
            confirmButtonText: "Aceptar"
        }).then(() => { window.location.href = "calificar_evidencia.php?id=' . $evidencia_id . '"; });
    </script></body></html>';
}
?>