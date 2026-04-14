<?php
// mod/guardar_evidencia.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/EvidenciaDAO.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: evidencias.php');
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$tipo = $_POST['tipo'] ?? '';
$porcentaje = !empty($_POST['porcentaje']) ? floatval($_POST['porcentaje']) : null;
$fecha = $_POST['fecha'] ?? '';
$tiempo_entrega = $_POST['tiempo_entrega'] ?? '';
$ficha_id = intval($_POST['ficha_id'] ?? 0);

$errores = [];
if (empty($nombre)) $errores[] = 'El nombre es obligatorio.';
if (empty($fecha)) $errores[] = 'La fecha es obligatoria.';
if (empty($tiempo_entrega)) $errores[] = 'El tiempo límite es obligatorio.';
if ($ficha_id <= 0) $errores[] = 'Debe seleccionar una ficha.';

if (!empty($errores)) {
    // Mostrar errores con SweetAlert y redirigir
    echo '<!DOCTYPE html><html><head><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script></head><body>';
    echo '<script>
        Swal.fire({
            icon: "error",
            title: "Error",
            html: "' . implode('<br>', $errores) . '",
            confirmButtonText: "Aceptar"
        }).then(() => { window.location.href = "crear_evidencia.php"; });
    </script></body></html>';
    exit;
}

$dao = new EvidenciaDAO();
$id = $dao->insertarEvidencia($nombre, $tipo, $porcentaje, $fecha, $tiempo_entrega, $ficha_id);

if ($id) {
    echo '<!DOCTYPE html><html><head><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script></head><body>';
    echo '<script>
        Swal.fire({
            icon: "success",
            title: "Evidencia creada",
            text: "La evidencia se ha guardado correctamente.",
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
            text: "No se pudo guardar la evidencia. Intente de nuevo.",
            confirmButtonText: "Aceptar"
        }).then(() => { window.location.href = "crear_evidencia.php"; });
    </script></body></html>';
}
?>