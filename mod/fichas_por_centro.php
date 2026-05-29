<?php
// mod/fichas_por_centro.php  ← va en mod/ directamente (no en subcarpeta)
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario_id'])) { echo '[]'; exit; }

$cid = (int)($_GET['centro_id']   ?? 0);
$pid = (int)($_GET['programa_id'] ?? 0);
$soloP = !empty($_GET['solo_programas']);

if (!$cid) { echo '[]'; exit; }

try {
    require_once __DIR__ . '/../conexion/conexion.php';

    class AjaxFichas extends BaseDatos {
        protected function consultar(){}protected function insertar(){}
        protected function actualizar(){}protected function eliminar(){}
        public function q($sql,$p=[]){
            $s=$this->ejecutarPreparado($sql,$p);
            return $s ? $s->fetchAll(PDO::FETCH_ASSOC) : [];
        }
    }
    $db = new AjaxFichas();

    if ($soloP) {
        echo json_encode($db->q(
            "SELECT DISTINCT p.PROGRAMA_ID, p.NOMBRE, p.NIVEL_FORMACION
             FROM ficha f INNER JOIN programa p ON f.PROGRAMA_ID=p.PROGRAMA_ID
             WHERE f.CENTRO_ID=:c AND f.ESTADO='Activa' ORDER BY p.NOMBRE",
            [':c'=>$cid]
        ));
    } else {
        $params=[':c'=>$cid]; $extra='';
        if($pid){$extra=' AND f.PROGRAMA_ID=:p';$params[':p']=$pid;}
        echo json_encode($db->q(
            "SELECT f.FICHA_ID, f.CODIGO_FICHA, f.ESTADO
             FROM ficha f WHERE f.CENTRO_ID=:c AND f.ESTADO='Activa'$extra
             ORDER BY f.CODIGO_FICHA",
            $params
        ));
    }
} catch(Exception $e){
    echo json_encode([]);
}