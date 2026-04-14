<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
$n = $_SESSION['usuario_nombre'] ?? 'Usuario';
$r = $_SESSION['usuario_rol'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Perfil</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
<?php include __DIR__ . '/../config/header.php'; ?>
<main class="container">
    <h1 class="page-title">Perfil</h1>
    <div class="content-card" style="padding:24px">
        <p><strong>Nombre:</strong> <?= htmlspecialchars($n) ?></p>
        <p><strong>Rol:</strong> <?= htmlspecialchars($r) ?></p>
        <a href="../index.php" class="btn-view-all">Volver al inicio</a>
    </div>
</main>
<?php include __DIR__ . '/../config/footer.php'; ?>
<script src="../js/tema.js"></script><script src="../js/profile_menu.js"></script><script src="../js/menu.js"></script>
</body></html>
