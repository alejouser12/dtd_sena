<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
if (!esAdmin() && !esInstructor()) {
    echo json_encode(['success' => false, 'message' => 'Sin permiso']);
    exit;
}
require_once __DIR__ . '/../conexion/HorarioDAO.php';

$fichaId = (int)$_POST['ficha_id'];
$trimestre = trim($_POST['trimestre']);
$fecha_desde = $_POST['fecha_desde'];
$fecha_hasta = $_POST['fecha_hasta'];
$dias = $_POST['dia'];
$horas_inicio = $_POST['hora_inicio'];
$horas_fin = $_POST['hora_fin'];
$materias = $_POST['materia'];
$aulas = $_POST['aula'];

$horarios = [];
for ($i = 0; $i < count($dias); $i++) {
    $horarios[] = [
        'dia' => (int)$dias[$i],
        'inicio' => $horas_inicio[$i],
        'fin' => $horas_fin[$i],
        'materia' => $materias[$i],
        'aula' => $aulas[$i] ?? ''
    ];
}
$datos = [
    'trimestre' => $trimestre,
    'fecha_desde' => $fecha_desde,
    'fecha_hasta' => $fecha_hasta,
    'horarios' => $horarios
];

$dao = new HorarioDAO();
$resultado = $dao->guardarHorario($fichaId, $datos);

echo json_encode(['success' => $resultado, 'message' => $resultado ? '' : 'Error al guardar']);
?>