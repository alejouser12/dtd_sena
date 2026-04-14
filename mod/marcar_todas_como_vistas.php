<?php
// mod/marcar_todas_como_vistas.php
session_start();
header('Content-Type: application/json');

// Guardar el timestamp actual como última vez que se vieron las alertas
$_SESSION['ultima_vista_alertas'] = time();

echo json_encode(['success' => true]);
?>