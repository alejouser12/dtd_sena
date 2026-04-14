<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/CentroDAO.php';

$dao = new CentroDAO();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: regionales.php');
    exit;
}

$centro = $dao->obtenerPorId($id);
if (!$centro) {
    header('Location: regionales.php');
    exit;
}

$programas = $dao->obtenerProgramas($id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($centro['NOMBRE']) ?> - Centro</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/regionales.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../config/header.php'; ?>
    
    <main class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <a href="regional_detalle.php?id=<?= $centro['REGIONAL_ID'] ?>" class="btn-view-all">
                <i class="fas fa-arrow-left"></i> Volver a la regional
            </a>
            <?php if (esAdmin()): ?>
            <div style="display: flex; gap: 10px;">
                <a href="crud/editar_centro.php?id=<?= $id ?>" class="btn-create">
                    <i class="fas fa-edit"></i> Editar Centro
                </a>
                <?php if (esAdmin()): ?>
                <a href="crud/crear_ficha.php?centro_id=<?= $id ?>" class="btn-create">
                    <i class="fas fa-layer-group"></i> Nueva ficha
                </a>
                <?php endif; ?>
                <a href="#" class="btn-cancel" onclick="confirmarEliminacion(<?= $id ?>)">
                    <i class="fas fa-trash-alt"></i> Eliminar
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Cabecera del centro -->
        <div class="content-card" style="margin: 20px 0; padding: 30px;">
            <h1 style="color: var(--color-verde-1); font-size: 32px; margin-bottom: 10px;">
                <?= htmlspecialchars($centro['NOMBRE']) ?>
            </h1>
            <p style="margin: 10px 0; font-size: 16px;">
                <i class="fas fa-globe"></i> <?= htmlspecialchars($centro['regional_nombre'] ?? '') ?>
                <?php if(!empty($centro['CODIGO'])): ?> | <i class="fas fa-barcode"></i> <?= htmlspecialchars($centro['CODIGO']) ?><?php endif; ?>
            </p>
            <div style="display: flex; gap: 30px; flex-wrap: wrap; margin-top: 15px;">
                <span><i class="fas fa-map-pin"></i> <?= htmlspecialchars($centro['DIRECCION'] ?? 'Dirección no disponible') ?></span>
                <span><i class="fas fa-phone"></i> <?= htmlspecialchars($centro['TELEFONO'] ?? 'Teléfono no disponible') ?></span>
            </div>
        </div>
        
        <h2><i class="fas fa-book"></i> Programas del Centro (<?= count($programas) ?>)</h2>
        
        <div class="programas-grid">
            <?php if (empty($programas)): ?>
                <div class="empty-state" style="grid-column: 1/-1;">
                    <i class="fas fa-folder-open"></i>
                    <h3>No hay programas</h3>
                    <p>Este centro no tiene programas registrados.</p>
                </div>
            <?php else: ?>
                <?php foreach($programas as $p): ?>
                <div class="programa-card" onclick="window.location.href='programa_detalle.php?id=<?= $p['PROGRAMA_ID'] ?>'">
                    <div class="programa-header">
                        <div class="programa-icon">
                            <i class="fas fa-<?= $p['NIVEL_FORMACION'] == 'Tecnólogo' ? 'microchip' : 'tools' ?>"></i>
                        </div>
                        <h3><?= htmlspecialchars($p['NOMBRE']) ?></h3>
                        <div class="programa-nivel">
                            <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($p['NIVEL_FORMACION']) ?>
                        </div>
                        <span class="programa-duracion">
                            <i class="far fa-clock"></i> <?= $p['DURACION_MESES'] ?> meses
                        </span>
                    </div>
                    <div class="programa-body">
                        <div class="programa-stats">
                            <div class="stat-item">
                                <span class="valor"><?= $p['total_fichas'] ?></span>
                                <span class="etiqueta">Fichas</span>
                            </div>
                            <div class="stat-item">
                                <span class="valor"><?= $p['total_aprendices'] ?></span>
                                <span class="etiqueta">Aprendices</span>
                            </div>
                        </div>
                        <span class="programa-estado">
                            <i class="fas fa-circle"></i> <?= $p['ESTADO'] ?>
                        </span>
                    </div>
                    <div class="programa-footer">
                        <span class="btn-ver-fichas">
                            Ver fichas <i class="fas fa-arrow-right"></i>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include '../config/footer.php'; ?>
    
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
                title: '¿Eliminar centro?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'crud/eliminar_centro.php?id=' + id;
                }
            });
        }

        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>