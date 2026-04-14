<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';

if (!esAdmin()) {
    header('Location: ../regionales.php');
    exit;
}

require_once __DIR__ . '/../../conexion/RegionalDAO.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ../regionales.php');
    exit;
}

class RegionalDeleteDAO extends RegionalDAO
{
    public function eliminarRegional($id)
    {
        $sql = 'DELETE FROM regional WHERE REGIONAL_ID = :id';
        $stmt = $this->ejecutarPreparado($sql, [':id' => $id]);
        return (bool)$stmt;
    }
}

$dao = new RegionalDeleteDAO();
$resultado = $dao->eliminarRegional($id);

if ($resultado) {
    $_SESSION['mensaje'] = 'Regional eliminada correctamente';
    $_SESSION['tipo_mensaje'] = 'success';
} else {
    $_SESSION['mensaje'] = 'Error al eliminar la regional';
    $_SESSION['tipo_mensaje'] = 'error';
}

header('Location: ../regionales.php');
exit;
