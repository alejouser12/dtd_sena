<?php
// mod/contar_notificaciones_nuevas.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/AlertaDAO.php';

$alertaDAO = new AlertaDAO();

// Obtener el timestamp de la última vista
$ultimaVista = isset($_SESSION['ultima_vista_alertas']) ? $_SESSION['ultima_vista_alertas'] : 0;

// Obtener alertas nuevas desde esa fecha
$alertasNuevas = $alertaDAO->obtenerAlertasNuevasDesde($ultimaVista);

$total = count($alertasNuevas);
$altas = 0;
$medias = 0;
$bajas = 0;

foreach ($alertasNuevas as $alerta) {
    switch($alerta['NIVEL']) {
        case 'Alto': $altas++; break;
        case 'Medio': $medias++; break;
        case 'Bajo': $bajas++; break;
    }
}

echo json_encode([
    'total' => $total,
    'altas' => $altas,
    'medias' => $medias,
    'bajas' => $bajas
]);
?>