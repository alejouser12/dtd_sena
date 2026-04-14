<?php
// mod/observacion.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/ObservacionDAO.php';
require_once __DIR__ . '/../conexion/AlertaDAO.php';

try {
    if (!isset($_POST['estudiante_id']) || !isset($_POST['tipo']) || !isset($_POST['nivel_riesgo']) || !isset($_POST['descripcion'])) {
        throw new Exception('Faltan datos requeridos');
    }

    $observacionDAO = new ObservacionDAO();
    $alertaDAO = new AlertaDAO();

    $datos = [
        'estudiante_id' => (int)$_POST['estudiante_id'],
        'instructor_id' => isset($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : (isset($_SESSION['instructor_id']) ? (int)$_SESSION['instructor_id'] : 1),
        'tipo' => $_POST['tipo'],
        'descripcion' => trim($_POST['descripcion']),
        'nivel_riesgo' => $_POST['nivel_riesgo']
    ];

    if (empty($datos['descripcion'])) {
        throw new Exception('La descripción no puede estar vacía');
    }

    // Guardar observación
    $observacion_id = $observacionDAO->guardarObservacion($datos);

    if ($observacion_id) {
        // Crear alerta automáticamente
        $alertaDAO->crearAlertaDesdeObservacion($datos, $observacion_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Observación guardada correctamente'
        ]);
    } else {
        throw new Exception('Error al guardar la observación');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>