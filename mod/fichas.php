<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
if (!esAdmin()) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../conexion/FichaDAO.php';
$fichas = (new FichaDAO())->obtenerTodas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichas</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div id="loader"><img src="../img/logo_sena_verde.png" alt="" id="loader-logo"></div>
    <?php include __DIR__ . '/../config/header.php'; ?>
    <main class="container" id="contenido-principal" style="display:none;opacity:0">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-layer-group"></i> Fichas</h1>
            <p class="page-subtitle">Listado de fichas del sistema</p>
        </div>
        <div style="margin-bottom:20px">
            <a href="crud/crear_ficha.php" class="btn-create"><i class="fas fa-plus"></i> Nueva ficha</a>
            <a href="../index.php" class="btn-view-all" style="margin-left:12px"><i class="fas fa-home"></i> Inicio</a>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Código</th><th>Programa</th><th>Centro</th><th>Estado</th><th>Aprendices</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($fichas as $f): ?>
                    <tr>
                        <td><?= htmlspecialchars($f['CODIGO_FICHA']) ?></td>
                        <td><?= htmlspecialchars($f['programa_nombre'] ?? '') ?></td>
                        <td><?= htmlspecialchars($f['centro_nombre'] ?? '') ?></td>
                        <td><?= htmlspecialchars($f['ESTADO'] ?? '') ?></td>
                        <td><?= (int)($f['total_aprendices'] ?? 0) ?></td>
                        <td><a class="btn-view-all" href="ficha_detalle.php?id=<?= (int)$f['FICHA_ID'] ?>"><i class="fas fa-eye"></i></a>
                            <a class="btn-edit" href="crud/editar_ficha.php?id=<?= (int)$f['FICHA_ID'] ?>"><i class="fas fa-edit"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    <?php include __DIR__ . '/../config/footer.php'; ?>
    <script src="../js/tema.js"></script><script src="../js/loader.js"></script>
    <script src="../js/profile_menu.js"></script><script src="../js/menu.js"></script>
</body>
</html>
