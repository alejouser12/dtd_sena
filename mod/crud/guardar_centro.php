<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/auth.php';

if (!esAdmin()) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para crear/editar centros']);
    exit;
}

require_once __DIR__ . '/../../conexion/CentroDAO.php';

class CentroEditDAO extends CentroDAO
{
    public function insertarCentro($datos)
    {
        $sql = 'INSERT INTO centro (REGIONAL_ID, NOMBRE, CODIGO, DIRECCION, TELEFONO, ESTADO)
                VALUES (:regional_id, :nombre, :codigo, :direccion, :telefono, :estado)';
        $params = [
            ':regional_id' => $datos['regional_id'],
            ':nombre' => $datos['nombre'],
            ':codigo' => !empty($datos['codigo']) ? $datos['codigo'] : null,
            ':direccion' => !empty($datos['direccion']) ? $datos['direccion'] : null,
            ':telefono' => !empty($datos['telefono']) ? $datos['telefono'] : null,
            ':estado' => $datos['estado'],
        ];
        $stmt = $this->ejecutarPreparado($sql, $params);
        return $stmt ? $this->Conexion_ID->lastInsertId() : false;
    }

    public function actualizarCentro($id, $datos)
    {
        $sql = 'UPDATE centro SET REGIONAL_ID = :regional_id, NOMBRE = :nombre, CODIGO = :codigo,
                DIRECCION = :direccion, TELEFONO = :telefono, ESTADO = :estado WHERE CENTRO_ID = :id';
        $params = [
            ':id' => $id,
            ':regional_id' => $datos['regional_id'],
            ':nombre' => $datos['nombre'],
            ':codigo' => !empty($datos['codigo']) ? $datos['codigo'] : null,
            ':direccion' => !empty($datos['direccion']) ? $datos['direccion'] : null,
            ':telefono' => !empty($datos['telefono']) ? $datos['telefono'] : null,
            ':estado' => $datos['estado'],
        ];
        $stmt = $this->ejecutarPreparado($sql, $params);
        return (bool)$stmt;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (empty($_POST['regional_id']) || empty($_POST['nombre']) || empty($_POST['estado'])) {
    echo json_encode(['success' => false, 'message' => 'La regional, nombre y estado son obligatorios']);
    exit;
}

$dao = new CentroEditDAO();

if (!empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    $resultado = $dao->actualizarCentro($id, $_POST);
    if ($resultado) {
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el centro']);
    }
} else {
    $id = $dao->insertarCentro($_POST);
    if ($id) {
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear el centro']);
    }
}
