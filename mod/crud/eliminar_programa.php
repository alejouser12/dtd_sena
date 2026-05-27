<?php
// mod/crud/eliminar_programa.php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../programas.php'); exit; }
require_once __DIR__ . '/../../conexion/ProgramaDAO.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ../programas.php'); exit; }

class ProgramaEliminar extends ProgramaDAO {
    public function borrarRegistro(int $id): bool {
        $stmt = $this->ejecutarPreparado(
            'DELETE FROM programa WHERE PROGRAMA_ID = :id',
            [':id' => $id]
        );
        return $stmt !== false;
    }
}

$del = new ProgramaEliminar();
$del->borrarRegistro($id);

header('Location: ../programas.php');
exit;