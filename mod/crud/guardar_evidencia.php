<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) {
    header('Location: ../evidencias.php');
    exit;
}
require_once __DIR__ . '/../../conexion/EvidenciaDAO.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../evidencias.php'); exit; }
$dao = new EvidenciaDAO();
if (!empty($_POST['evidencias_id'])) {
    $dao->actualizarEvidencia((int)$_POST['evidencias_id'], $_POST['nombre'], $_POST['tipo_evidencia'], (float)$_POST['porcentaje'], $_POST['fecha_evidencia'], $_POST['tiempo_entrega'], (int)$_POST['ficha_id'], $_POST['estado_evidencia'] ?? 'activa');
} else {
    $dao->crearEvidencia($_POST['nombre'], $_POST['tipo_evidencia'], (float)$_POST['porcentaje'], $_POST['fecha_evidencia'], $_POST['tiempo_entrega'], (int)$_POST['ficha_id'], $_POST['estado_evidencia'] ?? 'activa');
}
header('Location: ../evidencias.php');
exit;
