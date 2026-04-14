<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/FichaDAO.php';
require_once __DIR__ . '/../conexion/AprendizDAO.php';

$fichaDAO = new FichaDAO();
$aprendizDAO = new AprendizDAO();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: programas.php');
    exit;
}

$ficha = $fichaDAO->obtenerPorId($id);

if (!$ficha) {
    header('Location: programas.php');
    exit;
}

$aprendices = $fichaDAO->obtenerAprendices($id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha <?php echo $ficha['CODIGO_FICHA']; ?> - Detalle</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/ficha_detalle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div id="loader">
        <img src="../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo">
    </div>

    <?php include "../config/header.php"; ?>

    <main class="container" id="contenido-principal">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <a href="programa_detalle.php?id=<?php echo $ficha['PROGRAMA_ID']; ?>" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver al programa
            </a>
            <?php if (esAdmin()): ?>
            <div style="display: flex; gap: 10px;">
                <a href="editar_ficha.php?id=<?= $id ?>" class="btn-create">
                    <i class="fas fa-edit"></i> Editar Ficha
                </a>
                <a href="#" class="btn-cancel" onclick="confirmarEliminacion(<?= $id ?>)">
                    <i class="fas fa-trash-alt"></i> Eliminar
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Cabecera de la Ficha -->
        <div class="ficha-detalle-header">
            <div class="ficha-codigo">Ficha <?php echo $ficha['CODIGO_FICHA']; ?></div>
            <div class="ficha-programa">
                <i class="fas fa-book"></i>
                <?php echo $ficha['programa_nombre']; ?> - <?php echo $ficha['NIVEL_FORMACION']; ?>
            </div>
            
            <div class="ficha-info-grid">
                <div class="info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <small>Inicio</small>
                        <strong><?php echo date('d/m/Y', strtotime($ficha['FECHA_INICIO'])); ?></strong>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-calendar-check"></i>
                    <div>
                        <small>Fin</small>
                        <strong><?php echo date('d/m/Y', strtotime($ficha['FECHA_FIN'])); ?></strong>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-users"></i>
                    <div>
                        <small>Aprendices</small>
                        <strong><?php echo count($aprendices); ?></strong>
                    </div>
                </div>
                <?php if (!empty($ficha['HORARIO'])): ?>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <small>Horario</small>
                        <strong><?php echo $ficha['HORARIO']; ?></strong>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($ficha['AULA'])): ?>
                <div class="info-item">
                    <i class="fas fa-door-open"></i>
                    <div>
                        <small>Aula</small>
                        <strong><?php echo $ficha['AULA']; ?></strong>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- INFORMACIÓN DE UBICACIÓN (CENTRO Y REGIONAL) -->
            <?php if(!empty($ficha['centro_nombre'])): ?>
            <div style="margin-top: 20px; padding: 15px 20px; background: rgba(255,255,255,0.1); border-radius: 12px; backdrop-filter: blur(5px);">
                <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-building" style="font-size: 18px;"></i>
                        <div>
                            <small>Centro</small><br>
                            <strong><?php echo htmlspecialchars($ficha['centro_nombre']); ?></strong>
                            <?php if(!empty($ficha['centro_codigo'])): ?>
                                <span style="font-size: 11px; opacity: 0.8;"> (<?php echo $ficha['centro_codigo']; ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if(!empty($ficha['regional_nombre'])): ?>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-map-marker-alt" style="font-size: 18px;"></i>
                        <div>
                            <small>Regional</small><br>
                            <strong><?php echo htmlspecialchars($ficha['regional_nombre']); ?></strong>
                            <?php if(!empty($ficha['regional_ciudad'])): ?>
                                <span style="font-size: 11px; opacity: 0.8;"> - <?php echo $ficha['regional_ciudad']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Lista de Aprendices -->
        <div class="section-header">
            <h2><i class="fas fa-user-graduate"></i> Aprendices de la Ficha (<?php echo count($aprendices); ?>)</h2>
        </div>

        <?php if (empty($aprendices)): ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <h3>No hay aprendices</h3>
                <p>Esta ficha no tiene aprendices asignados actualmente.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Promedio</th>
                            <th>Asistencia</th>
                            <th>Estado</th>
                            <th>Riesgo</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($aprendices as $ap): 
                            $riesgoClass = '';
                            $riesgoTexto = $ap['NIVEL_RIESGO_GLOBAL'] ?? 'N/A';
                            
                            if($riesgoTexto == 'Bajo') $riesgoClass = 'bajo';
                            elseif($riesgoTexto == 'Medio') $riesgoClass = 'medio';
                            elseif($riesgoTexto == 'Alto') $riesgoClass = 'alto';
                            else $riesgoClass = 'na';
                            
                            $estadoClass = $ap['ESTADO_ACADEMICO'] == 'Activo' ? 'success' : 'danger';
                        ?>
                        <tr>
                            <td><?php echo $ap['TIPO_DOCUMENTO'] . ' ' . $ap['NUMERO_DOCUMENTO']; ?></td>
                            <td><strong><?php echo $ap['NOMBRES'] . ' ' . $ap['APELLIDOS']; ?></strong></td>
                            <td><?php echo $ap['EMAIL']; ?></td>
                            <td><?php echo $ap['TELEFONO']; ?></td>
                            <td><?php echo number_format($ap['PROMEDIO_GENERAL'] ?? 0, 1); ?></td>
                            <td><?php echo number_format($ap['PORCENTAJE_ASISTENCIA'] ?? 0, 1); ?>%</td>
                            <td>
                                <span class="badge badge-<?php echo $estadoClass; ?>">
                                    <?php echo $ap['ESTADO_ACADEMICO']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="riesgo-<?php echo $riesgoClass; ?>">
                                    <?php echo $riesgoTexto; ?>
                                </span>
                            </td>
                            <td>
                                <a href="aprendiz_detalle.php?id=<?php echo $ap['APRENDIZ_ID']; ?>" class="btn-ver-aprendiz">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
        function confirmarEliminacion(id) {
            Swal.fire({
                title: '¿Eliminar ficha?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'eliminar_ficha.php?id=' + id;
                }
            });
        }

        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>