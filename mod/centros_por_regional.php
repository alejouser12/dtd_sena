<?php
// mod/centros_por_regional.php  ← va en mod/ directamente (no en subcarpeta)
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario_id'])) { echo '[]'; exit; }

$rid = (int)($_GET['regional_id'] ?? 0);
if (!$rid) { echo '[]'; exit; }

try {
    require_once __DIR__ . '/../conexion/conexion.php';

    class AjaxCentros extends BaseDatos {
        protected function consultar(){}protected function insertar(){}
        protected function actualizar(){}protected function eliminar(){}
        public function q($sql,$p=[]){
            $s=$this->ejecutarPreparado($sql,$p);
            return $s ? $s->fetchAll(PDO::FETCH_ASSOC) : [];
        }
    }
    echo json_encode((new AjaxCentros())->q(
        "SELECT CENTRO_ID, NOMBRE, CIUDAD FROM centro WHERE REGIONAL_ID=:r ORDER BY NOMBRE",
        [':r'=>$rid]
    ));
} catch(Exception $e){
    echo json_encode([]);
}