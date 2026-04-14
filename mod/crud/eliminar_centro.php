<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../regionales.php'); exit; }
require_once __DIR__ . '/../../conexion/CentroDAO.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ../regionales.php'); exit; }
$dao = new CentroDAO();
$c = $dao->obtenerPorId($id);
$rid = $c ? (int)$c['REGIONAL_ID'] : 0;
class CentroDel extends CentroDAO {
    public function eliminar($id) { return (bool)$this->ejecutarPreparado('DELETE FROM centro WHERE CENTRO_ID = :id', [':id' => $id]); }
}
$d = new CentroDel();
$d->eliminar($id);
header('Location: ' . ($rid ? '../regional_detalle.php?id=' . $rid : '../regionales.php'));
exit;
