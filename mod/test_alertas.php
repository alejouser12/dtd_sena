<?php
// mod/test_alertas.php
require_once __DIR__ . '/../conexion/AlertaDAO.php';

$dao = new AlertaDAO();

echo "<h1>🔍 DEBUG - Alertas en el sistema</h1>";

// Usar el nuevo método para obtener todas las alertas
$alertas = $dao->obtenerTodasAlertas();

echo "<h2>📊 Total alertas en BD: " . count($alertas) . "</h2>";

if (count($alertas) > 0) {
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse; width:100%; font-family: Arial;'>";
    echo "<tr style='background:#39a900; color:white;'>
            <th>ID</th>
            <th>Estudiante</th>
            <th>Nivel</th>
            <th>Descripción</th>
            <th>Regla</th>
            <th>Fecha</th>
            <th>Estado</th>
          </tr>";
    
    foreach ($alertas as $a) {
        $color = $a['ESTADO'] == 'Activa' ? '#ffebee' : '#e8f5e9';
        echo "<tr style='background: $color'>";
        echo "<td>{$a['ALERTA_ID']}</td>";
        echo "<td><strong>{$a['NOMBRES']} {$a['APELLIDOS']}</strong></td>";
        echo "<td>";
        switch($a['NIVEL']) {
            case 'Alto': echo "<span style='color:#dc2626; font-weight:bold;'>🔴 Alto</span>"; break;
            case 'Medio': echo "<span style='color:#f59e0b; font-weight:bold;'>🟡 Medio</span>"; break;
            case 'Bajo': echo "<span style='color:#39a900; font-weight:bold;'>🟢 Bajo</span>"; break;
            default: echo $a['NIVEL'];
        }
        echo "</td>";
        echo "<td>{$a['DESCRIPCION']}</td>";
        echo "<td>{$a['NOMBRE_REGLA']}</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($a['FECHA_GENERACION'])) . "</td>";
        echo "<td><strong>" . ($a['ESTADO'] == 'Activa' ? '✅ ACTIVA' : '❌ Inactiva') . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:#f59e0b; font-size:18px;'>⚠️ No hay alertas en la base de datos</p>";
}

echo "<h2>📈 Conteo de alertas activas:</h2>";
$conteo = $dao->obtenerConteoAlertas();
echo "<pre style='background:#f5f5f5; padding:15px; border-radius:8px;'>";
print_r($conteo);
echo "</pre>";

echo "<h2>🔔 Alertas activas (método obtenerAlertasActivas):</h2>";
$activas = $dao->obtenerAlertasActivas();
echo "<pre style='background:#f5f5f5; padding:15px; border-radius:8px;'>";
print_r($activas);
echo "</pre>";

echo "<hr>";
echo "<h3>ℹ️ Información de depuración:</h3>";
echo "<ul>";
echo "<li>Método ejecutarSQL: " . (method_exists($dao, 'ejecutarSQL') ? '✅' : '❌') . "</li>";
echo "<li>Método cargarTodo: " . (method_exists($dao, 'cargarTodo') ? '✅' : '❌') . "</li>";
echo "<li>Clase BaseDatos: " . (class_exists('BaseDatos') ? '✅' : '❌') . "</li>";
echo "</ul>";
?>