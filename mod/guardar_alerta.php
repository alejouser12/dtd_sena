<?php
// mod/guardar_alerta.php
session_start();
header('Content-Type: application/json');

try {
    // Verificar que llegaron los datos
    if (!isset($_POST['estudiante_id']) || !isset($_POST['tipo']) || !isset($_POST['nivel_riesgo']) || !isset($_POST['descripcion'])) {
        throw new Exception('Faltan datos requeridos');
    }

    require_once __DIR__ . '/../conexion/SeguimientoDAO.php';

    $seguimientoDAO = new SeguimientoDAO();

    // Preparar datos
    $datos = [
        'estudiante_id' => (int)$_POST['estudiante_id'],
        'instructor_id' => isset($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : (int)($_SESSION['usuario_ref_id'] ?? 0),
        'tipo' => $_POST['tipo'] ?? '',
        'descripcion' => trim($_POST['descripcion']),
        'nivel_riesgo' => $_POST['nivel_riesgo']
    ];

    // Validar campos vacíos
    if (empty($datos['descripcion'])) {
        throw new Exception('La descripción no puede estar vacía');
    }

    // Crear alerta
    $resultado = $seguimientoDAO->registrarDesdeAlerta($datos);

    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Alerta creada correctamente y sincronizada con observaciones',
            'data' => $resultado
        ]);
        exit;
    } else {
        throw new Exception('Error al crear la alerta sincronizada');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
