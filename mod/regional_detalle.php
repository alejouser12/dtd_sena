<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/RegionalDAO.php';

$dao = new RegionalDAO();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: regionales.php');
    exit;
}

$regional = $dao->obtenerPorId($id);
if (!$regional) {
    header('Location: regionales.php');
    exit;
}

$centros = $dao->obtenerCentros($id);

// Calcular totales
$totalFichas = array_sum(array_column($centros, 'total_fichas'));
$totalAprendices = array_sum(array_column($centros, 'total_aprendices'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($regional['NOMBRE']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/regionales.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../config/header.php'; ?>
    
    <main class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <a href="regionales.php" class="btn-view-all"><i class="fas fa-arrow-left"></i> Volver a regionales</a>
            <?php if (esAdmin()): ?>
            <div style="display: flex; gap: 10px;">
                <a href="crud/editar_regional.php?id=<?= $id ?>" class="btn-create">
                    <i class="fas fa-edit"></i> Editar Regional
                </a>
                <a href="crud/crear_centro.php?regional_id=<?= $id ?>" class="btn-create">
                    <i class="fas fa-plus"></i> Nuevo centro
                </a>
                <a href="#" class="btn-cancel" onclick="confirmarEliminacion(<?= $id ?>)">
                    <i class="fas fa-trash-alt"></i> Eliminar
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="content-card" style="margin: 20px 0; padding: 30px;">
            <h1 style="color: var(--color-verde-1); margin-bottom: 10px;"><?= htmlspecialchars($regional['NOMBRE']) ?></h1>
            <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                <span><i class="fas fa-city"></i> <?= htmlspecialchars($regional['CIUDAD'] ?? 'N/A') ?></span>
                <span><i class="fas fa-phone"></i> <?= htmlspecialchars($regional['TELEFONO'] ?? 'N/A') ?></span>
                <span><i class="fas fa-map-pin"></i> <?= htmlspecialchars($regional['DIRECCION'] ?? 'N/A') ?></span>
            </div>
        </div>
        
        <div class="stats-grid" style="margin-bottom: 30px;">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-building"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= count($centros) ?></span>
                    <span class="stat-label">Centros</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-book-open"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= $totalFichas ?></span>
                    <span class="stat-label">Fichas</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= $totalAprendices ?></span>
                    <span class="stat-label">Aprendices</span>
                </div>
            </div>
        </div>
        
        <h2><i class="fas fa-building"></i> Centros de Formación (<?= count($centros) ?>)</h2>
        
        <div class="centros-grid">
            <?php if (empty($centros)): ?>
                <div class="empty-state" style="grid-column: 1/-1;">
                    <i class="fas fa-school"></i>
                    <p>No hay centros en esta regional</p>
                </div>
            <?php else: ?>
                <?php foreach($centros as $c): ?>
                <div class="centro-card" onclick="window.location.href='centro_detalle.php?id=<?= $c['CENTRO_ID'] ?>'">
                    <div class="centro-header">
                        <i class="fas fa-school" style="font-size: 30px;"></i>
                        <h3><?= htmlspecialchars($c['NOMBRE']) ?></h3>
                        <small><?= htmlspecialchars($c['CODIGO'] ?? '') ?></small>
                    </div>
                    <div class="centro-body">
                        <p><i class="fas fa-map-pin"></i> <?= htmlspecialchars($c['DIRECCION'] ?? 'N/A') ?></p>
                        <p><i class="fas fa-phone"></i> <?= htmlspecialchars($c['TELEFONO'] ?? 'N/A') ?></p>
                        <div class="centro-stats">
                            <div class="stat">
                                <span class="valor"><?= $c['total_fichas'] ?></span>
                                <span class="etiqueta">Fichas</span>
                            </div>
                            <div class="stat">
                                <span class="valor"><?= $c['total_aprendices'] ?></span>
                                <span class="etiqueta">Aprendices</span>
                            </div>
                        </div>
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
                title: '¿Eliminar regional?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'crud/eliminar_regional.php?id=' + id;
                }
            });
        }

        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>