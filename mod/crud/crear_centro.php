<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../regionales.php'); exit; }
require_once __DIR__ . '/../../conexion/RegionalDAO.php';

// Obtener la regional desde la URL
$regionalId = isset($_GET['regional_id']) ? (int)$_GET['regional_id'] : 0;
$regionales = (new RegionalDAO())->obtenerTodas();

// Si no se pasa regional válida, redirigir a regionales
if ($regionalId <= 0) {
    header('Location: ../regionales.php');
    exit;
}

// Buscar la regional seleccionada para mostrarla (opcional)
$regionalSeleccionada = null;
foreach ($regionales as $r) {
    if ($r['REGIONAL_ID'] == $regionalId) {
        $regionalSeleccionada = $r;
        break;
    }
}
if (!$regionalSeleccionada) {
    header('Location: ../regionales.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Centro - DTD SENA</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-container { max-width: 700px; margin: 0 auto; }
        .form-header {
            background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2));
            padding: 25px;
            border-radius: 20px 20px 0 0;
            color: white;
            text-align: center;
        }
        .form-header i { font-size: 50px; margin-bottom: 10px; }
        .form-header h2 { margin: 0; font-size: 28px; }
        .form-header p { margin: 5px 0 0; opacity: 0.9; }
        .card-body { padding: 30px; }
        .form-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; }
        @media (max-width: 768px) {
            .form-actions { flex-direction: column; }
            .form-actions .btn-cancel, .form-actions .btn-action { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <div id="loader"><img src="../../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo"></div>
    <?php include __DIR__ . '/../../config/header.php'; ?>
    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div class="form-container">
            <div class="form-header">
                <i class="fas fa-building"></i>
                <h2>Nuevo Centro de Formación</h2>
                <p>Registre un centro para la regional <strong><?= htmlspecialchars($regionalSeleccionada['NOMBRE']) ?></strong></p>
            </div>
            <div class="content-card" style="border-radius: 0 0 20px 20px; margin-top: 0;">
                <div class="card-body">
                    <form id="form-centro">
                        <!-- Regional (oculta o readonly) -->
                        <input type="hidden" name="regional_id" value="<?= $regionalId ?>">
                        <div class="form-group">
                            <label><i class="fas fa-globe"></i> Regional *</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($regionalSeleccionada['NOMBRE']) ?>" disabled readonly>
                            <small class="form-text text-muted">Centro asociado a esta regional</small>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Nombre *</label>
                            <input type="text" class="form-control" name="nombre" placeholder="Ej: Centro de Electricidad" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-barcode"></i> Código</label>
                            <input type="text" class="form-control" name="codigo" placeholder="Código interno">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-map-pin"></i> Dirección</label>
                            <input type="text" class="form-control" name="direccion" placeholder="Dirección">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Teléfono</label>
                            <input type="text" class="form-control" name="telefono" placeholder="Teléfono">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-toggle-on"></i> Estado</label>
                            <select name="estado" class="form-control">
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <a href="../regional_detalle.php?id=<?= $regionalId ?>" class="btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
                            <button type="submit" class="btn-action"><i class="fas fa-save"></i> Guardar Centro</button>
                        </div>
                    </form>
                </div>
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
        document.getElementById('form-centro').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('guardar_centro.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Centro creado!',
                            text: 'Redirigiendo...',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = '../regional_detalle.php?id=<?= $regionalId ?>';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'No se pudo guardar'
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de red',
                        text: 'No se pudo conectar con el servidor'
                    });
                });
        });
        if (typeof initThemeToggle === 'function') setTimeout(initThemeToggle, 100);
    </script>
</body>
</html>