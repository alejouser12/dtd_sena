<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) {
    header('Location: ../regionales.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Regional</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div id="loader"><img src="../../img/logo_sena_verde.png" alt="" id="loader-logo"></div>
    <?php include __DIR__ . '/../../config/header.php'; ?>
    <main class="container" id="contenido-principal" style="display:none;opacity:0">
        <a href="../regionales.php" class="btn-view-all"><i class="fas fa-arrow-left"></i> Volver</a>
        <div class="content-card" style="margin-top:20px">
            <div class="card-header"><h2><i class="fas fa-plus-circle"></i> Nueva regional</h2></div>
            <div class="card-body">
                <form id="form-regional">
                    <div class="form-group"><label>Nombre *</label><input class="form-control" name="nombre" required></div>
                    <div class="form-group"><label>Código</label><input class="form-control" name="codigo"></div>
                    <div class="form-group"><label>Ciudad</label><input class="form-control" name="ciudad"></div>
                    <div class="form-group"><label>Dirección</label><input class="form-control" name="direccion"></div>
                    <div class="form-group"><label>Teléfono</label><input class="form-control" name="telefono"></div>
                    <div class="form-group"><label>Estado *</label>
                        <select class="form-control" name="estado" required>
                            <option value="Activa">Activa</option>
                            <option value="Inactiva">Inactiva</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <a href="../regionales.php" class="btn-cancel">Cancelar</a>
                        <button type="submit" class="btn-action"><i class="fas fa-save"></i> Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include __DIR__ . '/../../config/footer.php'; ?>
    <script src="../../js/tema.js"></script>
    <script src="../../js/loader.js"></script>
    <script src="../../js/panel_menu.js"></script>
    <script src="../../js/dropdowns.js"></script>
    <script src="../../js/profile_menu.js"></script>
    <script src="../../js/sweetalerts.js"></script>
    <script src="../../js/menu.js"></script>
    <script>
    document.getElementById('form-regional').addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fetch('guardar_regional.php', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => {
                if (d.success) Swal.fire({ icon: 'success', title: 'Listo', timer: 1200, showConfirmButton: false })
                    .then(() => { window.location.href = '../regionales.php'; });
                else Swal.fire({ icon: 'error', title: 'Error', text: d.message || '' });
            }).catch(() => Swal.fire({ icon: 'error', title: 'Error de red' }));
    });
    </script>
</body>
</html>
