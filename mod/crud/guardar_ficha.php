<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { echo json_encode(['success' => false, 'message' => 'No autorizado']); exit; }
require_once __DIR__ . '/../../conexion/conexion.php';

class FichaGuard extends BaseDatos {
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}
    public function guardar($post) {
        $cod = trim($post['codigo_ficha'] ?? '');
        $pid = (int)($post['programa_id'] ?? 0);
        $cid = (int)($post['centro_id'] ?? 0);
        $ini = $post['fecha_inicio'] ?? '';
        $fin = $post['fecha_fin'] ?? '';
        $est = $post['estado'] ?? 'Activa';
        if ($cod === '' || $pid <= 0 || $cid <= 0 || $ini === '' || $fin === '') return false;
        if (!empty($post['id'])) {
            $sql = 'UPDATE ficha SET CODIGO_FICHA=:c, PROGRAMA_ID=:p, CENTRO_ID=:ce, FECHA_INICIO=:i, FECHA_FIN=:f, ESTADO=:e WHERE FICHA_ID=:id';
            return (bool)$this->ejecutarPreparado($sql, [':id' => (int)$post['id'], ':c' => $cod, ':p' => $pid, ':ce' => $cid, ':i' => $ini, ':f' => $fin, ':e' => $est]);
        }
        $sql = 'INSERT INTO ficha (CODIGO_FICHA, PROGRAMA_ID, CENTRO_ID, FECHA_INICIO, FECHA_FIN, ESTADO) VALUES (:c,:p,:ce,:i,:f,:e)';
        $st = $this->ejecutarPreparado($sql, [':c' => $cod, ':p' => $pid, ':ce' => $cid, ':i' => $ini, ':f' => $fin, ':e' => $est]);
        return $st ? (int)$this->Conexion_ID->lastInsertId() : false;
    }
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }
$g = new FichaGuard();
$r = $g->guardar($_POST);
if ($r === false) echo json_encode(['success' => false, 'message' => 'Datos inválidos o error BD']);
elseif (is_int($r)) echo json_encode(['success' => true, 'id' => $r]);
else echo json_encode(['success' => true]);
