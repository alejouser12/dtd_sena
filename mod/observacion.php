<?php
// mod/observacion.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/SeguimientoDAO.php';

try {
    if (!isset($_POST['estudiante_id']) || !isset($_POST['tipo']) || !isset($_POST['nivel_riesgo']) || !isset($_POST['descripcion'])) {
        throw new Exception('Faltan datos requeridos');
    }

    $seguimientoDAO = new SeguimientoDAO();

    $datos = [
        'estudiante_id' => (int)$_POST['estudiante_id'],
        'instructor_id' => isset($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : (int)($_SESSION['usuario_ref_id'] ?? 0),
        'tipo' => $_POST['tipo'],
        'descripcion' => trim($_POST['descripcion']),
        'nivel_riesgo' => $_POST['nivel_riesgo']
    ];

    if (empty($datos['descripcion'])) {
        throw new Exception('La descripción no puede estar vacía');
    }

    // Registrar observación y alerta en una sola transacción
    $resultado = $seguimientoDAO->registrarDesdeObservacion($datos);

    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Observación guardada correctamente y sincronizada con alertas',
            'data' => $resultado
        ]);
    } else {
        throw new Exception('Error al guardar la observación sincronizada');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
