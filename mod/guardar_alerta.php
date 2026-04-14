<?php
// mod/guardar_alerta.php
session_start();
header('Content-Type: application/json');

// Activar reporte de errores TEMPORALMENTE
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Verificar que llegaron los datos
    if (!isset($_POST['estudiante_id']) || !isset($_POST['tipo']) || !isset($_POST['nivel_riesgo']) || !isset($_POST['descripcion'])) {
        throw new Exception('Faltan datos requeridos');
    }

    require_once __DIR__ . '/../conexion/AlertaDAO.php';
    require_once __DIR__ . '/../conexion/ObservacionDAO.php';

    $alertaDAO = new AlertaDAO();
    $observacionDAO = new ObservacionDAO();

    // Preparar datos
    $datos = [
        'estudiante_id' => (int)$_POST['estudiante_id'],
        'instructor_id' => isset($_SESSION['instructor_id']) ? (int)$_SESSION['instructor_id'] : 1,
        'tipo' => $_POST['tipo'] ?? '',
        'descripcion' => trim($_POST['descripcion']),
        'nivel_riesgo' => $_POST['nivel_riesgo']
    ];

    // Validar campos vacíos
    if (empty($datos['descripcion'])) {
        throw new Exception('La descripción no puede estar vacía');
    }

    // Crear alerta
    $resultado = $alertaDAO->crearAlertaDirecta($datos);

    if ($resultado) {
        // Registrar observación paralela para que aparezca en el detalle del aprendiz
        $observacionId = $observacionDAO->guardarObservacion([
            'estudiante_id' => $datos['estudiante_id'],
            'instructor_id' => $datos['instructor_id'],
            'tipo' => $datos['tipo'],
            'descripcion' => $datos['descripcion'],
            'nivel_riesgo' => $datos['nivel_riesgo']
        ]);
        if (!$observacionId) {
            error_log('No se pudo guardar la observación asociada a la alerta directa');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Alerta creada correctamente'
        ]);
    } else {
        throw new Exception('Error al crear la alerta en la base de datos');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>