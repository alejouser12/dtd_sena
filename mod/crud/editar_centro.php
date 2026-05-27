<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../regionales.php'); exit; }
require_once __DIR__ . '/../../conexion/CentroDAO.php';
require_once __DIR__ . '/../../conexion/RegionalDAO.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ../regionales.php'); exit; }

$centroDAO = new CentroDAO();
$centro = $centroDAO->obtenerPorId($id);
if (!$centro) { header('Location: ../regionales.php'); exit; }

$regionalDAO = new RegionalDAO();
$regionales = $regionalDAO->obtenerTodas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Centro - DTD SENA</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-container {
            max-width: 700px;
            margin: 0 auto;
        }
        .form-header {
            background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2));
            padding: 25px;
            border-radius: 20px 20px 0 0;
            color: white;
            text-align: center;
        }
        .form-header i {
            font-size: 50px;
            margin-bottom: 10px;
        }
        .form-header h2 {
            margin: 0;
            font-size: 28px;
        }
        .form-header p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        .card-body {
            padding: 30px;
        }
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
            }
            .form-actions .btn-cancel,
            .form-actions .btn-action {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div id="loader"><img src="../../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo"></div>
    <?php include __DIR__ . '/../../config/header.php'; ?>
    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div class="form-container">
            <div class="form-header">
                <i class="fas fa-edit"></i>
                <h2>Editar Centro</h2>
                <p>Actualice los datos del centro de formación</p>
            </div>
            <div class="content-card" style="border-radius: 0 0 20px 20px; margin-top: 0;">
                <div class="card-body">
                    <form id="form-centro">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="form-group">
                            <label><i class="fas fa-globe"></i> Regional *</label>
                            <select class="form-control" name="regional_id" required>
                                <?php foreach ($regionales as $r): ?>
                                    <option value="<?= (int)$r['REGIONAL_ID'] ?>" <?= (int)$centro['REGIONAL_ID'] === (int)$r['REGIONAL_ID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['NOMBRE']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Nombre *</label>
                            <input type="text" class="form-control" name="nombre" value="<?= htmlspecialchars($centro['NOMBRE']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-barcode"></i> Código</label>
                            <input type="text" class="form-control" name="codigo" value="<?= htmlspecialchars($centro['CODIGO'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-map-pin"></i> Dirección</label>
                            <input type="text" class="form-control" name="direccion" value="<?= htmlspecialchars($centro['DIRECCION'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Teléfono</label>
                            <input type="text" class="form-control" name="telefono" value="<?= htmlspecialchars($centro['TELEFONO'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-toggle-on"></i> Estado *</label>
                            <select class="form-control" name="estado" required>
                                <option value="Activo" <?= ($centro['ESTADO'] ?? '') === 'Activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="Inactivo" <?= ($centro['ESTADO'] ?? '') === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <a href="../centro_detalle.php?id=<?= $id ?>" class="btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
                            <button type="submit" class="btn-action"><i class="fas fa-save"></i> Guardar Cambios</button>
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
            fetch('guardar_centro.php', { method: 'POST', body: new FormData(this) })
                .then(r => r.json()).then(d => {
                    if (d.success) {
                        Swal.fire({ icon: 'success', title: 'Centro actualizado', timer: 1500, showConfirmButton: false })
                            .then(() => location.href = '../centro_detalle.php?id=<?= $id ?>');
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: d.message || 'No se pudo guardar' });
                    }
                })
                .catch(() => Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo conectar con el servidor' }));
        });
        if (typeof initThemeToggle === 'function') setTimeout(initThemeToggle, 100);
    </script>
</body>
</html>