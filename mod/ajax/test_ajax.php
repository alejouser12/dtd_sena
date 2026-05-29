<?php
// mod/ajax/test_ajax.php — SOLO PARA DIAGNOSTICO, BORRA DESPUÉS
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html>
<head><title>Test AJAX</title></head>
<body style="font-family:monospace;padding:20px;background:#111;color:#0f0;">
<h2>Diagnóstico AJAX — Centros por Regional</h2>

<p>Session usuario_id: <strong><?= $_SESSION['usuario_id'] ?? 'NO HAY SESIÓN' ?></strong></p>
<p>Session rol: <strong><?= $_SESSION['usuario_rol'] ?? 'NO HAY ROL' ?></strong></p>

<hr>
<h3>Test directo PHP → BD</h3>
<?php
try {
    require_once __DIR__ . '/../../conexion/conexion.php';
    
    // Test con PDO directo sin clase
    $config = require __DIR__ . '/../../conexion/config.php';
    // Si no existe config.php intentamos con la clase
} catch (Exception $e) {
    echo "<p style='color:red'>Error config: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    // Intentar query directa
    class TestDB extends BaseDatos {
        protected function consultar(){}protected function insertar(){}
        protected function actualizar(){}protected function eliminar(){}
        public function q($sql,$p=[]){
            $s=$this->ejecutarPreparado($sql,$p);
            return $s?$s->fetchAll(PDO::FETCH_ASSOC):[];
        }
    }
    $db = new TestDB();
    
    $regionales = $db->q("SELECT REGIONAL_ID, NOMBRE FROM regional LIMIT 5");
    echo "<p>✓ Regionales en BD: <strong>" . count($regionales) . "</strong></p>";
    foreach ($regionales as $r) echo "<p>  → ID={$r['REGIONAL_ID']} | {$r['NOMBRE']}</p>";
    
    $centros = $db->q("SELECT CENTRO_ID, NOMBRE, REGIONAL_ID FROM centro WHERE REGIONAL_ID=1 LIMIT 5");
    echo "<p>✓ Centros de Regional 1: <strong>" . count($centros) . "</strong></p>";
    foreach ($centros as $c) echo "<p>  → ID={$c['CENTRO_ID']} | {$c['NOMBRE']}</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error BD: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<hr>
<h3>Test fetch AJAX</h3>
<button onclick="testAjax()" style="padding:10px 20px;font-size:14px;cursor:pointer;">Probar fetch a centros_por_regional.php?regional_id=1</button>
<pre id="resultado" style="background:#222;padding:14px;margin-top:10px;color:#0f0;min-height:60px;"></pre>

<script>
async function testAjax() {
    const pre = document.getElementById('resultado');
    pre.textContent = 'Cargando...';
    try {
        // Prueba ruta relativa
        const url = 'centros_por_regional.php?regional_id=1';
        pre.textContent += '\nURL: ' + url;
        const r = await fetch(url);
        const text = await r.text();
        pre.textContent += '\nStatus: ' + r.status;
        pre.textContent += '\nContent-Type: ' + r.headers.get('content-type');
        pre.textContent += '\nRespuesta: ' + text;
    } catch(e) {
        pre.textContent = 'ERROR: ' + e.message;
    }
}
</script>
</body>
</html>