<?php
// mod/crud/guardar_ficha.php
session_start();
require_once __DIR__ . '/../../config/auth.php';
header('Content-Type: application/json; charset=utf-8');

if (!esAdmin()) {
    echo json_encode(['success'=>false,'message'=>'Sin permisos.']); exit;
}

require_once __DIR__ . '/../../conexion/conexion.php';

class GuardarFichaDB extends BaseDatos {
    protected function consultar(){}protected function insertar(){}
    protected function actualizar(){}protected function eliminar(){}
    public function exec($sql,$p=[]){ return $this->ejecutarPreparado($sql,$p); }
    public function uno($sql,$p=[]){ $s=$this->ejecutarPreparado($sql,$p); return $s?$s->fetch(PDO::FETCH_ASSOC):[]; }
    public function lastId(){ $s=$this->ejecutarPreparado("SELECT LAST_INSERT_ID() AS id",[]); $r=$s?$s->fetch(PDO::FETCH_ASSOC):null; return $r?(int)$r['id']:0; }
}
$db = new GuardarFichaDB();

$id          = (int)($_POST['id']          ?? 0);
$codigo      = trim($_POST['codigo_ficha'] ?? '');
$programaId  = (int)($_POST['programa_id'] ?? 0);
$centroId    = (int)($_POST['centro_id']   ?? 0);
$fechaInicio = trim($_POST['fecha_inicio'] ?? '');
$fechaFin    = trim($_POST['fecha_fin']    ?? '');
$estado      = trim($_POST['estado']       ?? 'Activa');
$gestorId    = (int)($_POST['gestor_instructor_id'] ?? 0);

if (!$codigo || !$programaId || !$centroId || !$fechaInicio || !$fechaFin) {
    echo json_encode(['success'=>false,'message'=>'Faltan campos obligatorios.']); exit;
}

try {
    if ($id > 0) {
        // EDITAR
        $db->exec(
            "UPDATE ficha SET CODIGO_FICHA=:c, PROGRAMA_ID=:p, CENTRO_ID=:ct,
              FECHA_INICIO=:fi, FECHA_FIN=:ff, ESTADO=:e
             WHERE FICHA_ID=:id",
            [':c'=>$codigo,':p'=>$programaId,':ct'=>$centroId,
             ':fi'=>$fechaInicio,':ff'=>$fechaFin,':e'=>$estado,':id'=>$id]
        );
        $fichaId = $id;
    } else {
        // CREAR
        $db->exec(
            "INSERT INTO ficha (CODIGO_FICHA,PROGRAMA_ID,CENTRO_ID,FECHA_INICIO,FECHA_FIN,ESTADO)
             VALUES (:c,:p,:ct,:fi,:ff,:e)",
            [':c'=>$codigo,':p'=>$programaId,':ct'=>$centroId,
             ':fi'=>$fechaInicio,':ff'=>$fechaFin,':e'=>$estado]
        );
        $fichaId = $db->lastId();
    }

    // ── Actualizar gestor ──────────────────────────────────────────
    // 1. Quitar la gestoría de esta ficha a cualquier instructor que la tenga
    $db->exec(
        "UPDATE instructor SET GESTOR_FICHA_ID = NULL
         WHERE GESTOR_FICHA_ID = :fid",
        [':fid' => $fichaId]
    );

    // 2. Asignar al nuevo gestor (si se seleccionó uno)
    if ($gestorId > 0) {
        // Quitar la gestoría de cualquier otra ficha que tuviera este instructor
        $db->exec(
            "UPDATE instructor SET GESTOR_FICHA_ID = :fid
             WHERE INSTRUCTOR_ID = :iid",
            [':fid' => $fichaId, ':iid' => $gestorId]
        );

        // Asegurarse de que el gestor tenga asignada esta ficha en instructor_ficha
        $db->exec(
            "INSERT IGNORE INTO instructor_ficha (INSTRUCTOR_ID, FICHA_ID)
             VALUES (:iid, :fid)",
            [':iid' => $gestorId, ':fid' => $fichaId]
        );
    }

    echo json_encode(['success'=>true,'id'=>$fichaId]);

} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}