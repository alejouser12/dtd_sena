<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { echo json_encode(['success' => false]); exit; }
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../conexion/InstructorDAO.php';

// Clase auxiliar para operaciones simples (compatibilidad con el código original)
class I extends BaseDatos {
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}
}

$n = trim($_POST['nombres'] ?? '');
$a = trim($_POST['apellidos'] ?? '');
$e = trim($_POST['email'] ?? '');
$esp = trim($_POST['especialidad'] ?? '');
$gestorFicha = isset($_POST['gestor_ficha_id']) ? (int)$_POST['gestor_ficha_id'] : 0;

if ($n === '' || $a === '' || $e === '') {
    echo json_encode(['success' => false, 'message' => 'Complete nombres, apellidos y email']);
    exit;
}

$instructorId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$fichasSeleccionadas = isset($_POST['fichas']) ? $_POST['fichas'] : [];
$fichasSeleccionadas = array_map('intval', $fichasSeleccionadas);
if ($gestorFicha && !in_array($gestorFicha, $fichasSeleccionadas, true)) {
    $gestorFicha = 0;
}

// Si se está actualizando un instructor existente
if ($instructorId > 0) {
    // Actualizar datos básicos
    $i = new I();
    $ok = $i->ejecutarPreparado(
        'UPDATE instructor SET NOMBRES=:n, APELLIDOS=:a, EMAIL=:e, ESPECIALIDAD=:s, GESTOR_FICHA_ID=:gf WHERE INSTRUCTOR_ID=:id',
        [':id' => $instructorId, ':n' => $n, ':a' => $a, ':e' => $e, ':s' => $esp ?: null, ':gf' => ($gestorFicha ?: null)]
    );
    if ($ok) {
        // Actualizar fichas usando el InstructorDAO
        $dao = new InstructorDAO();
        $fichasOk = $dao->actualizarFichas($instructorId, $fichasSeleccionadas);
        echo json_encode(['success' => true, 'message' => 'Instructor actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar los datos del instructor']);
    }
} else {
    // Crear un nuevo instructor
    $i = new I();
    $ok = $i->ejecutarPreparado(
        'INSERT INTO instructor (NOMBRES, APELLIDOS, EMAIL, ESPECIALIDAD, GESTOR_FICHA_ID) VALUES (:n, :a, :e, :s, :gf)',
        [':n' => $n, ':a' => $a, ':e' => $e, ':s' => $esp ?: null, ':gf' => ($gestorFicha ?: null)]
    );
    if ($ok) {
        $nuevoId = (int)$i->obtenerUltimoId();
        // Asignar fichas si las hay
        if (!empty($fichasSeleccionadas)) {
            $dao = new InstructorDAO();
            $dao->actualizarFichas($nuevoId, $fichasSeleccionadas);
        }
        echo json_encode(['success' => true, 'id' => $nuevoId, 'message' => 'Instructor creado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear el instructor']);
    }
}
?>