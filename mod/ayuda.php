<?php session_start(); require_once __DIR__ . '/../config/auth.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Ayuda</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
<?php include __DIR__ . '/../config/header.php'; ?>
<main class="container">
    <h1 class="page-title">Ayuda</h1>
    <div class="content-card" style="padding:24px">
        <p>Use el menú de módulos para navegar. Si no ve una opción, su rol puede no tener permisos.</p>
        <a href="../index.php" class="btn-view-all">Inicio</a>
    </div>
</main>
<?php include __DIR__ . '/../config/footer.php'; ?>
<script src="../js/tema.js"></script><script src="../js/profile_menu.js"></script><script src="../js/menu.js"></script>
</body></html>
