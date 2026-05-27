<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/AprendizDAO.php';

if (!esAprendiz()) {
    redirect_to('index.php');
}

$aprendizDAO = new AprendizDAO();
$usuarioId = $_SESSION['usuario_id'] ?? 0;

$sql = "SELECT APRENDIZ_ID, NOMBRES, APELLIDOS
        FROM aprendiz
        WHERE usuario_id = :usuario_id
        LIMIT 1";
$stmt = $aprendizDAO->ejecutarPreparado($sql, [':usuario_id' => $usuarioId]);
$aprendiz = $stmt ? $stmt->fetch() : null;

if (!$aprendiz) {
    redirect_to('index.php');
}

$aprendizId = $aprendiz['APRENDIZ_ID'];

$faltas = $aprendizDAO->obtenerFaltasConJustificacionPendiente($aprendizId);

$historialCompleto = $aprendizDAO->obtenerAsistencia($aprendizId, 50);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Faltas - DTD SENA</title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .faltas-pendientes {
            margin-bottom: 40px;
        }
        .faltas-pendientes h2 {
            margin-bottom: 15px;
            color: var(--color-verde-1);
            font-size: 1.4rem;
        }
        .faltas-pendientes .empty-state {
            padding: 30px;
            text-align: center;
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            border: 1px solid var(--border-color);
        }
        .tabla-faltas {
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            overflow: hidden;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-color);
        }
        .tabla-faltas table {
            width: 100%;
            border-collapse: collapse;
        }
        .tabla-faltas th {
            background: var(--color-gris-fondo);
            padding: 12px 15px;
            text-align: left;
            font-weight: 700;
            color: var(--color-texto-secundario);
            border-bottom: 2px solid var(--border-color);
        }
        .tabla-faltas td {
            padding: 10px 15px;
            border-bottom: 1px solid var(--border-color);
        }
        .tabla-faltas tr:last-child td {
            border-bottom: none;
        }
        .badge-pendiente {
            background: #fef3c7;
            color: #d97706;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-justificada {
            background: #dcfce7;
            color: #16a34a;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .btn-justificar {
            background: var(--color-verde-1);
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-justificar:hover {
            background: var(--color-verde-2);
            transform: translateY(-1px);
        }
        .fecha-vencida {
            color: #dc2626;
            font-size: 11px;
            margin-top: 4px;
        }
        .dark-mode .tabla-faltas {
            background: var(--color-gris-cuerpo);
        }
        .dark-mode .badge-pendiente {
            background: #78350f;
            color: #fed7aa;
        }
        .dark-mode .badge-justificada {
            background: #14532d;
            color: #bbf7d0;
        }
        @media (max-width: 768px) {
            .tabla-faltas th, .tabla-faltas td {
                padding: 8px 10px;
                font-size: 12px;
            }
            .btn-justificar {
                padding: 4px 10px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div id="loader">
        <img src="<?= asset_url('img/logo_sena_verde.png') ?>" alt="Logo SENA" id="loader-logo">
    </div>

    <?php include __DIR__ . '/../config/header.php'; ?>

    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-calendar-times"></i> Historial de Faltas
            </h1>
            <p class="page-subtitle">Consulta y justifica tus inasistencias</p>
        </div>

        <?php if (!empty($faltas)): ?>
        <div class="faltas-pendientes">
            <h2><i class="fas fa-exclamation-triangle"></i> Faltas pendientes por justificar</h2>
            <div class="tabla-faltas">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Horas</th>
                            <th>Fecha límite</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faltas as $falta): 
                            $limite = $falta['FECHA_LIMITE_EXCUSA'] ?? null;
                            $vencida = $limite && date('Y-m-d') > $limite;
                        ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($falta['FECHA'])) ?></td>
                            <td><?= (int)($falta['HORAS_FALTA'] ?? 4) ?> horas</td>
                            <td>
                                <?php if ($limite): ?>
                                    <?= date('d/m/Y', strtotime($limite)) ?>
                                    <?php if (!$vencida): ?>
                                        <span class="badge-pendiente">Plazo activo</span>
                                    <?php else: ?>
                                        <div class="fecha-vencida"><i class="fas fa-clock"></i> Vencido</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Sin plazo definido
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$vencida): ?>
                                    <button class="btn-justificar" onclick="abrirJustificacion(<?= $falta['ASISTENCIA_ID'] ?>, '<?= date('d/m/Y', strtotime($falta['FECHA'])) ?>')">
                                        <i class="fas fa-file-alt"></i> Justificar
                                    </button>
                                <?php else: ?>
                                    <span class="badge-justificada">Plazo vencido</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="empty-state" style="margin-bottom: 30px;">
            <i class="fas fa-check-circle"></i>
            <h3>No hay faltas pendientes</h3>
            <p>Todas tus inasistencias están justificadas o no tienes faltas registradas.</p>
        </div>
        <?php endif; ?>

        <h2><i class="fas fa-history"></i> Historial completo de asistencias</h2>
        <div class="tabla-faltas" style="margin-top: 15px;">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Horas</th>
                        <th>Excusa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historialCompleto)): ?>
                    <tr>
                        <td colspan="4" class="empty-state">No hay registros de asistencia</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($historialCompleto as $reg): 
                            $estado = $reg['ESTADO'];
                            $badgeClass = '';
                            $estadoTexto = '';
                            if ($estado == 'asistio') {
                                $badgeClass = 'badge-a';
                                $estadoTexto = 'Presente';
                            } elseif ($estado == 'retardo') {
                                $badgeClass = 'badge-r';
                                $estadoTexto = 'Retardo';
                            } elseif ($estado == 'falta') {
                                $badgeClass = 'badge-f';
                                $estadoTexto = 'Falta';
                            } elseif ($estado == 'excusa') {
                                $badgeClass = 'badge-e';
                                $estadoTexto = 'Excusa';
                            } else {
                                $badgeClass = '';
                                $estadoTexto = ucfirst($estado);
                            }
                        ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($reg['FECHA'])) ?></td>
                            <td><span class="<?= $badgeClass ?>"><?= $estadoTexto ?></span></td>
                            <td>
                                <?php if ($estado == 'retardo' && ($reg['HORAS_FALTA'] ?? 0) > 0): ?>
                                    <?= (int)$reg['HORAS_FALTA'] ?> horas de retardo
                                <?php elseif ($estado == 'falta'): ?>
                                    4 horas
                                <?php elseif ($estado == 'excusa'): ?>
                                    0 (justificado)
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($estado == 'excusa'): ?>
                                    <i class="fas fa-check-circle" style="color: #16a34a;"></i> Presentada
                                <?php elseif ($estado == 'falta'): ?>
                                    <i class="fas fa-clock" style="color: #f59e0b;"></i> Pendiente
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <a href="<?= app_url('mod/aprendiz_perfil.php') ?>" class="btn-view-all">
                <i class="fas fa-arrow-left"></i> Volver a Mi Perfil
            </a>
        </div>
    </main>

    <?php include __DIR__ . '/../config/footer.php'; ?>

    <script src="<?= asset_url('js/tema.js') ?>"></script>
    <script src="<?= asset_url('js/loader.js') ?>"></script>
    <script src="<?= asset_url('js/dropdowns.js') ?>"></script>
    <script src="<?= asset_url('js/profile_menu.js') ?>"></script>
    <script src="<?= asset_url('js/sweetalerts.js') ?>"></script>
    <script src="<?= asset_url('js/menu.js') ?>"></script>

    <script>
        function abrirJustificacion(asistenciaId, fecha) {
            Swal.fire({
                title: 'Justificar falta del ' + fecha,
                html: `
                    <textarea id="justificacion_texto" class="swal2-textarea" placeholder="Escribe tu justificación aquí..." rows="4" style="width:100%;"></textarea>
                `,
                showCancelButton: true,
                confirmButtonText: 'Enviar justificación',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const texto = document.getElementById('justificacion_texto').value;
                    if (!texto.trim()) {
                        Swal.showValidationMessage('Debes escribir una justificación');
                        return false;
                    }
                    return texto;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('<?= app_url('mod/guardar_justificacion.php') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'asistencia_id=' + asistenciaId + '&justificacion=' + encodeURIComponent(result.value)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Justificación enviada',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: error.message
                        });
                    });
                }
            });
        }

        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>