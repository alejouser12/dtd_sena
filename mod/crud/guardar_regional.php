<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/auth.php';

if (!esAdmin()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../conexion/RegionalDAO.php';

try {
    $nombre = trim($_POST['nombre'] ?? '');
    if ($nombre === '') {
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
        exit;
    }

    $dao = new RegionalDAO();
    $datos = [
        'nombre' => $nombre,
        'codigo' => trim($_POST['codigo'] ?? ''),
        'ciudad' => trim($_POST['ciudad'] ?? ''),
        'direccion' => trim($_POST['direccion'] ?? ''),
        'telefono' => trim($_POST['telefono'] ?? ''),
        'estado' => $_POST['estado'] ?? 'Activa',
    ];

    if (!empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $ok = $dao->actualizarRegional($id, $datos);
        $newId = $id;
    } else {
        $newId = $dao->crearRegional($datos);
        $ok = $newId !== false;
    }

    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Guardado correctamente', 'id' => $newId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar en la base de datos']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
