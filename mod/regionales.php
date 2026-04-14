<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/RegionalDAO.php';

$dao = new RegionalDAO();
$regionales = $dao->obtenerTodas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regionales SENA</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/regionales.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div id="loader">
        <img src="../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo">
    </div>

    <?php include '../config/header.php'; ?>
    
    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        
        <!-- SOLO ESTO ES EL BOTÓN -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="margin:0; color: #39a900;">Regionales SENA</h1>
            
            <?php if (esAdmin()): ?>
            <a href="crud/crear_regional.php" class="btn-create">
                <i class="fas fa-plus"></i> Nueva Regional
            </a>
            <?php endif; ?>
        </div>
        <!-- FIN DEL BOTÓN -->
        
        <div class="regionales-grid">
            <?php if (empty($regionales)): ?>
                <p>No hay regionales registradas</p>
            <?php else: ?>
                <?php foreach($regionales as $r): ?>
                <div class="regional-card" onclick="window.location.href='regional_detalle.php?id=<?= $r['REGIONAL_ID'] ?>'">
                    <div class="regional-header">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3><?= htmlspecialchars($r['NOMBRE']) ?></h3>
                        <span class="regional-codigo"><?= htmlspecialchars($r['CODIGO'] ?? '') ?></span>
                    </div>
                    <div class="regional-body">
                        <div class="regional-info">
                            <i class="fas fa-city"></i>
                            <span><?= htmlspecialchars($r['CIUDAD'] ?? 'Ciudad no especificada') ?></span>
                        </div>
                        <div class="regional-info">
                            <i class="fas fa-phone"></i>
                            <span><?= htmlspecialchars($r['TELEFONO'] ?? 'Teléfono no disponible') ?></span>
                        </div>
                        <div class="regional-info">
                            <i class="fas fa-map-pin"></i>
                            <span><?= htmlspecialchars($r['DIRECCION'] ?? 'Dirección no especificada') ?></span>
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
    <script src="../js/menu.js"></script>
    <script src="../js/profile_menu.js"></script>
    
</body>
</html>