<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../regionales.php'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Regional</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-container { max-width: 700px; margin: 0 auto; }
        .form-header { background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2)); padding: 20px; border-radius: 20px 20px 0 0; color: white; text-align: center; }
        .form-header i { font-size: 50px; margin-bottom: 10px; }
        .card-body { padding: 30px; }
        .form-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; }
        @media (max-width: 768px) { .form-actions { flex-direction: column; } .form-actions .btn-cancel, .form-actions .btn-action { width: 100%; justify-content: center; } }
    </style>
</head>
<body>
    <div id="loader"><img src="../../img/logo_sena_verde.png" alt="" id="loader-logo"></div>
    <?php include __DIR__ . '/../../config/header.php'; ?>
    <main class="container" id="contenido-principal" style="display:none;opacity:0">
        <div class="form-container">
            <div class="form-header">
                <i class="fas fa-map-marked-alt"></i>
                <h2>Nueva Regional</h2>
                <p>Complete los datos para registrar una nueva regional SENA</p>
            </div>
            <div class="content-card" style="border-radius: 0 0 20px 20px; margin-top:0;">
                <div class="card-body">
                    <form id="form-regional">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Nombre *</label>
                            <input type="text" class="form-control" name="nombre" placeholder="Ej: Regional Distrito Capital" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-barcode"></i> Código</label>
                            <input type="text" class="form-control" name="codigo" placeholder="Código interno">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-city"></i> Ciudad</label>
                            <input type="text" class="form-control" name="ciudad" placeholder="Ciudad principal">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-map-pin"></i> Dirección</label>
                            <input type="text" class="form-control" name="direccion" placeholder="Dirección">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Teléfono</label>
                            <input type="text" class="form-control" name="telefono" placeholder="Teléfono de contacto">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-toggle-on"></i> Estado</label>
                            <select name="estado" class="form-control">
                                <option value="Activa">Activa</option>
                                <option value="Inactiva">Inactiva</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <a href="../regionales.php" class="btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
                            <button type="submit" class="btn-action"><i class="fas fa-save"></i> Guardar Regional</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <?php include __DIR__ . '/../../config/footer.php'; ?>
    <script src="../../js/tema.js"></script><script src="../../js/loader.js"></script>
    <script src="../../js/panel_menu.js"></script><script src="../../js/dropdowns.js"></script>
    <script src="../../js/profile_menu.js"></script><script src="../../js/sweetalerts.js"></script><script src="../../js/menu.js"></script>
    <script>
    document.getElementById('form-regional').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('guardar_regional.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json()).then(d => {
                if (d.success) Swal.fire({ icon: 'success', title: '¡Regional creada!', text: 'Redirigiendo...', timer: 1500, showConfirmButton: false })
                    .then(() => window.location.href = '../regionales.php');
                else Swal.fire({ icon: 'error', title: 'Error', text: d.message || 'No se pudo guardar' });
            });
    });
    </script>
</body>
</html>