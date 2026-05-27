<?php
// mod/crud/eliminar_centro.php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../regionales.php'); exit; }
require_once __DIR__ . '/../../conexion/CentroDAO.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ../regionales.php'); exit; }

$dao = new CentroDAO();
$c   = $dao->obtenerPorId($id);
$rid = $c ? (int)$c['REGIONAL_ID'] : 0;

// Usamos ejecutarPreparado directo a través de un método con nombre distinto
// para no chocar con el protected eliminar() de BaseDatos
class CentroEliminar extends CentroDAO {
    public function borrarRegistro(int $id): bool {
        $stmt = $this->ejecutarPreparado(
            'DELETE FROM centro WHERE CENTRO_ID = :id',
            [':id' => $id]
        );
        return $stmt !== false;
    }
}

$del = new CentroEliminar();
$del->borrarRegistro($id);

header('Location: ' . ($rid ? '../regional_detalle.php?id=' . $rid : '../regionales.php'));
exit;