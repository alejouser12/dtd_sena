<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/FichaDAO.php';
require_once __DIR__ . '/../conexion/AsistenciaDAO.php';

$dao = new FichaDAO();
$asistenciaDAO = new AsistenciaDAO();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: asistencias.php');
    exit;
}

$ficha = $dao->obtenerPorId($id);
if (!$ficha) {
    header('Location: asistencias.php');
    exit;
}

$aprendices = $dao->obtenerAprendices($id);

// Lógica para obtener los días de la semana según filtro 
if (isset($_GET['semana']) && preg_match('/^\d{4}-W\d{1,2}$/', $_GET['semana'])) {
    $semanaSeleccionada = $_GET['semana'];
    list($anio, $semanaNum) = explode('-W', $semanaSeleccionada);
    $semanaNum = (int)$semanaNum;
    $fechaInicioSemana = new DateTime();
    $fechaInicioSemana->setISODate($anio, $semanaNum, 1);
} else {
    $fechaInicioSemana = new DateTime();
    $fechaInicioSemana->modify('monday this week');
    $semanaSeleccionada = $fechaInicioSemana->format('Y-\WW');
}

$diasSemana = [];
$diasNombres = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
for ($i = 0; $i < 6; $i++) {
    $fecha = clone $fechaInicioSemana;
    $fecha->modify("+$i days");
    $diasSemana[] = [
        'nombre' => $diasNombres[$i],
        'fecha' => $fecha->format('Y-m-d'),
        'fecha_formateada' => $fecha->format('d/m/Y')
    ];
}
$rangoSemana = 'Semana del ' . $diasSemana[0]['fecha_formateada'] . ' al ' . $diasSemana[5]['fecha_formateada'];

// Obtener asistencias ya guardadas para esta semana
$asistenciasGuardadas = $asistenciaDAO->obtenerAsistenciasSemana($id, $diasSemana[0]['fecha']);

// Procesar guardado si se envió el formulario
$mensaje = '';
$tipoMensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_asistencia'])) {
    if (!esAdmin() && !esInstructor()) {
        $tipoMensaje = 'error';
        $mensaje = 'No tienes permiso para guardar asistencias.';
    } else {
        $asistenciasMarcadas = [];
        $retardosMarcados = [];
        
        if (isset($_POST['asistencia']) && is_array($_POST['asistencia'])) {
            foreach ($_POST['asistencia'] as $aprendizId => $dias) {
                foreach ($dias as $fecha => $valor) {
                    if ($valor === 'on') {
                        if (!isset($asistenciasMarcadas[$aprendizId])) {
                            $asistenciasMarcadas[$aprendizId] = [];
                        }
                        $asistenciasMarcadas[$aprendizId][$fecha] = true;
                    }
                }
            }
        }
        
        if (isset($_POST['retardo']) && is_array($_POST['retardo'])) {
            foreach ($_POST['retardo'] as $aprendizId => $dias) {
                foreach ($dias as $fecha => $horas) {
                    if ($horas > 0) {
                        if (!isset($retardosMarcados[$aprendizId])) {
                            $retardosMarcados[$aprendizId] = [];
                        }
                        $retardosMarcados[$aprendizId][$fecha] = (int)$horas;
                    }
                }
            }
        }
        
        $resultado = $asistenciaDAO->guardarAsistenciasSemana(
            $id, 
            $diasSemana[0]['fecha'], 
            $asistenciasMarcadas,
            $retardosMarcados
        );
        
        if ($resultado) {
            $tipoMensaje = 'success';
            $mensaje = 'Asistencia guardada correctamente.';
            // Recargar las asistencias guardadas
            $asistenciasGuardadas = $asistenciaDAO->obtenerAsistenciasSemana($id, $diasSemana[0]['fecha']);
        } else {
            $tipoMensaje = 'error';
            $mensaje = 'Error al guardar la asistencia: ' . $asistenciaDAO->imprimirError();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistencia - Ficha <?= htmlspecialchars($ficha['CODIGO_FICHA']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --border-excel: #e0e0e0;
            --header-excel: #f5f5f5;
            --hover-excel: #fafafa;
        }
        .dark-mode {
            --border-excel: #404040;
            --header-excel: #2d2d2d;
            --hover-excel: #363636;
        }
        .asistencia-detalle-header {
            background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2));
            padding: 30px;
            border-radius: var(--border-radius-card);
            margin-bottom: 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .asistencia-detalle-header h1 {
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .asistencia-detalle-header .btn-volver {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }
        .asistencia-detalle-header .btn-volver:hover {
            background: rgba(255,255,255,0.3);
        }
        .info-ficha {
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-color);
        }
        .info-ficha-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .info-ficha-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .info-ficha-item i {
            font-size: 24px;
            color: var(--color-verde-1);
        }
        .filtro-semana {
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            padding: 20px 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }
        .filtro-semana .rango {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-verde-1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filtro-semana .selector {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .filtro-semana .selector label {
            font-weight: 600;
            color: var(--color-texto-secundario);
        }
        .filtro-semana .selector input[type="week"] {
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-input);
            font-size: 14px;
            background: var(--color-blanco);
            color: var(--color-texto);
        }
        .filtro-semana .selector button {
            background: var(--color-verde-1);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius-btn);
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .filtro-semana .selector button:hover {
            background: var(--color-verde-2);
            transform: translateY(-2px);
        }
        .table-container {
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            padding: 20px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-color);
            overflow-x: auto;
        }
        .table-container table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }
        .table-container th {
            background: var(--header-excel);
            color: var(--color-texto);
            font-weight: 700;
            padding: 15px;
            text-align: center;
            border: 1px solid var(--border-excel);
        }
        .table-container td {
            padding: 12px 15px;
            border: 1px solid var(--border-excel);
            text-align: center;
        }
        .table-container td:first-child {
            text-align: left;
            font-weight: 600;
            position: sticky;
            left: 0;
            background: var(--color-blanco);
            z-index: 5;
        }
        .table-container tr:hover td {
            background: var(--hover-excel);
        }
        .table-container tr:hover td:first-child {
            background: var(--hover-excel);
        }
        .checkbox-asistencia {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--color-verde-1);
        }
        .select-retardo {
            width: 70px;
            padding: 5px;
            border: 1px solid var(--border-excel);
            border-radius: 4px;
            background: var(--color-blanco);
            color: var(--color-texto);
            font-size: 13px;
        }
        .btn-guardar {
            background: var(--color-verde-1);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 18px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 30px 0 20px;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(57,169,0,0.3);
        }
        .btn-guardar:hover {
            background: var(--color-verde-2);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(57,169,0,0.4);
        }
        .acciones {
            display: flex;
            justify-content: center;
        }
        .resumen-asistencia {
            margin-top: 20px;
            padding: 15px;
            background: var(--color-gris-fondo);
            border-radius: 8px;
            display: flex;
            gap: 30px;
            font-size: 14px;
            flex-wrap: wrap;
        }
        .resumen-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .resumen-item i {
            font-size: 18px;
        }
        .total-horas {
            font-weight: 700;
            color: var(--color-verde-1);
        }
    </style>
</head>
<body>
    <div id="loader">
        <img src="../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo">
    </div>

    <?php include "../config/header.php"; ?>

    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div class="asistencia-detalle-header">
            <h1>
                <i class="fas fa-clipboard-list"></i> Asistencia - Ficha <?= htmlspecialchars($ficha['CODIGO_FICHA']) ?>
            </h1>
            <a href="asistencias.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver a fichas
            </a>
        </div>

        <div class="info-ficha">
            <h2 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-info-circle"></i> Detalles de la ficha
            </h2>
            <div class="info-ficha-grid">
                <div class="info-ficha-item">
                    <i class="fas fa-graduation-cap"></i>
                    <div>
                        <strong>Programa</strong><br>
                        <?= htmlspecialchars($ficha['programa_nombre']) ?>
                    </div>
                </div>
                <div class="info-ficha-item">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <strong>Inicio</strong><br>
                        <?= date('d/m/Y', strtotime($ficha['FECHA_INICIO'])) ?>
                    </div>
                </div>
                <div class="info-ficha-item">
                    <i class="fas fa-calendar-check"></i>
                    <div>
                        <strong>Fin</strong><br>
                        <?= date('d/m/Y', strtotime($ficha['FECHA_FIN'])) ?>
                    </div>
                </div>
                <div class="info-ficha-item">
                    <i class="fas fa-users"></i>
                    <div>
                        <strong>Aprendices</strong><br>
                        <?= $ficha['total_aprendices'] ?>
                    </div>
                </div>
                <div class="info-ficha-item">
                    <i class="fas fa-circle"></i>
                    <div>
                        <strong>Estado</strong><br>
                        <?= htmlspecialchars($ficha['ESTADO']) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="filtro-semana">
            <div class="rango">
                <i class="fas fa-calendar-week"></i> <?= $rangoSemana ?>
            </div>
            <form method="GET" action="" class="selector">
                <input type="hidden" name="id" value="<?= $id ?>">
                <label for="semana"><i class="fas fa-calendar-alt"></i> Seleccionar semana:</label>
                <input type="week" name="semana" id="semana" value="<?= $semanaSeleccionada ?>" required>
                <button type="submit">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </form>
        </div>

        <form method="POST" action="" id="form-asistencia">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <?php foreach ($diasSemana as $dia): ?>
                                <th colspan="2">
                                    <?= $dia['nombre'] ?><br>
                                    <small style="font-weight: normal;"><?= $dia['fecha_formateada'] ?></small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th></th>
                            <?php foreach ($diasSemana as $dia): ?>
                                <th>Asistió</th>
                                <th>Llegó tarde</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($aprendices)): ?>
                            <tr>
                                <td colspan="<?= (count($diasSemana) * 2) + 1 ?>" class="empty-state">
                                    <i class="fas fa-user-slash"></i> No hay aprendices en esta ficha
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($aprendices as $a): 
                                $aprendizId = $a['APRENDIZ_ID'];
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['NOMBRES'] . ' ' . $a['APELLIDOS']) ?></td>
                                    <?php foreach ($diasSemana as $dia): 
                                        $fecha = $dia['fecha'];
                                        $asistio = false;
                                        $retardoHoras = 0;
                                        
                                        if (isset($asistenciasGuardadas[$aprendizId])) {
                                            foreach ($asistenciasGuardadas[$aprendizId] as $reg) {
                                                if ($reg['fecha'] == $fecha) {
                                                    if ($reg['estado'] == 'asistio') {
                                                        $asistio = true;
                                                        $retardoHoras = 0;
                                                    } elseif ($reg['estado'] == 'retardo') {
                                                        $asistio = false;
                                                        $retardoHoras = $reg['horas_falta'];
                                                    } elseif ($reg['estado'] == 'falta') {
                                                        $asistio = false;
                                                        $retardoHoras = 0;
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                    ?>
                                        <td>
                                            <input type="checkbox" 
                                                   name="asistencia[<?= $aprendizId ?>][<?= $fecha ?>]" 
                                                   class="checkbox-asistencia"
                                                   <?= $asistio ? 'checked' : '' ?>
                                                   onchange="actualizarRetardo(this, <?= $aprendizId ?>, '<?= $fecha ?>')">
                                        </td>
                                        <td>
                                            <select name="retardo[<?= $aprendizId ?>][<?= $fecha ?>]" 
                                                    class="select-retardo"
                                                    id="retardo_<?= $aprendizId ?>_<?= str_replace('-', '_', $fecha) ?>"
                                                    onchange="actualizarAsistencia(this, <?= $aprendizId ?>, '<?= $fecha ?>')">
                                                <option value="0" <?= $retardoHoras == 0 ? 'selected' : '' ?>>0h</option>
                                                <option value="1" <?= $retardoHoras == 1 ? 'selected' : '' ?>>1h</option>
                                                <option value="2" <?= $retardoHoras == 2 ? 'selected' : '' ?>>2h</option>
                                                <option value="3" <?= $retardoHoras == 3 ? 'selected' : '' ?>>3h</option>
                                                <option value="4" <?= $retardoHoras == 4 ? 'selected' : '' ?>>4h</option>
                                            </select>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="resumen-asistencia" id="resumenHoras">
                <div class="resumen-item">
                    <i class="fas fa-check-circle" style="color: var(--color-verde-1);"></i>
                    <span>Asistencias: <strong id="total-asistencias">0</strong></span>
                </div>
                <div class="resumen-item">
                    <i class="fas fa-clock" style="color: #f59e0b;"></i>
                    <span>Retardos: <strong id="total-retardos">0</strong> ( <span id="horas-retardo">0</span>h )</span>
                </div>
                <div class="resumen-item">
                    <i class="fas fa-times-circle" style="color: #dc2626;"></i>
                    <span>Faltas: <strong id="total-faltas">0</strong> ( <span id="horas-falta">0</span>h )</span>
                </div>
                <div class="resumen-item">
                    <i class="fas fa-hourglass-half" style="color: var(--color-verde-1);"></i>
                    <span>Total horas inasistencia: <strong class="total-horas" id="total-horas-inasistencia">0</strong>h</span>
                </div>
            </div>

            <?php if (!empty($aprendices) && (esAdmin() || esInstructor())): ?>
            <div class="acciones">
                <button type="submit" name="guardar_asistencia" class="btn-guardar">
                    <i class="fas fa-save"></i> Guardar Asistencia
                </button>
            </div>
            <?php endif; ?>
        </form>

        <?php if ($mensaje): ?>
        <script>
            Swal.fire({
                icon: '<?= $tipoMensaje ?>',
                title: '<?= $tipoMensaje === 'success' ? '¡Éxito!' : 'Error' ?>',
                text: '<?= htmlspecialchars($mensaje) ?>',
                timer: 3000,
                showConfirmButton: false
            });
        </script>
        <?php endif; ?>
    </main>

    <?php include "../config/footer.php"; ?>

    <script src="../js/tema.js"></script>
    <script src="../js/loader.js"></script>
    <script src="../js/panel_menu.js"></script>
    <script src="../js/dropdowns.js"></script>
    <script src="../js/profile_menu.js"></script>
    <script src="../js/sweetalerts.js"></script>
    <script src="../js/menu.js"></script>

    <script>
        function actualizarResumen() {
            let totalAsistencias = 0;
            let totalRetardos = 0;
            let horasRetardo = 0;
            
            document.querySelectorAll('.checkbox-asistencia').forEach(cb => {
                if (cb.checked) {
                    totalAsistencias++;
                }
            });
            
            document.querySelectorAll('.select-retardo').forEach(select => {
                let horas = parseInt(select.value);
                if (horas > 0) {
                    totalRetardos++;
                    horasRetardo += horas;
                }
            });
            
            let totalDias = document.querySelectorAll('.checkbox-asistencia').length;
            let totalFaltas = totalDias - totalAsistencias - totalRetardos;
            let horasFalta = totalFaltas * 4;
            
            document.getElementById('total-asistencias').textContent = totalAsistencias;
            document.getElementById('total-retardos').textContent = totalRetardos;
            document.getElementById('horas-retardo').textContent = horasRetardo;
            document.getElementById('total-faltas').textContent = totalFaltas;
            document.getElementById('horas-falta').textContent = horasFalta;
            document.getElementById('total-horas-inasistencia').textContent = horasRetardo + horasFalta;
        }

        function actualizarRetardo(checkbox, aprendizId, fecha) {
            let selectId = 'retardo_' + aprendizId + '_' + fecha.replace(/-/g, '_');
            let select = document.getElementById(selectId);
            if (select && checkbox.checked) {
                select.value = '0';
            }
            actualizarResumen();
        }

        function actualizarAsistencia(select, aprendizId, fecha) {
            let checkboxName = `asistencia[${aprendizId}][${fecha}]`;
            let checkbox = document.querySelector(`input[name="${checkboxName}"]`);
            if (checkbox && select.value > 0) {
                checkbox.checked = false;
            }
            actualizarResumen();
        }

        document.querySelectorAll('.checkbox-asistencia, .select-retardo').forEach(el => {
            el.addEventListener('change', actualizarResumen);
        });
        
        actualizarResumen();

        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>