<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../programas.php'); exit; }
require_once __DIR__ . '/../../conexion/ProgramaDAO.php';
require_once __DIR__ . '/../../conexion/CentroDAO.php';

$programaDAO = new ProgramaDAO();
$centroDAO = new CentroDAO();

$programas = $programaDAO->obtenerProgramas();
$centros = $centroDAO->obtenerTodos();

// Obtener parámetros de la URL (para pre-selección y para el botón cancelar)
$preProg = isset($_GET['programa_id']) ? (int)$_GET['programa_id'] : 0;
$preCent = isset($_GET['centro_id']) ? (int)$_GET['centro_id'] : 0;

// Validar que el programa exista (para el botón cancelar)
$programaOrigen = null;
if ($preProg > 0) {
    $programaOrigen = $programaDAO->obtenerPorId($preProg);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Ficha - DTD SENA</title>
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
                <i class="fas fa-layer-group"></i>
                <h2>Nueva Ficha</h2>
                <p>Registre una nueva ficha de formación</p>
            </div>
            <div class="content-card" style="border-radius: 0 0 20px 20px; margin-top: 0;">
                <div class="card-body">
                    <form id="form-ficha">
                        <div class="form-group">
                            <label><i class="fas fa-barcode"></i> Código de ficha *</label>
                            <input type="text" class="form-control" name="codigo_ficha" placeholder="Ej: ADS2568914" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-graduation-cap"></i> Programa *</label>
                            <select class="form-control" name="programa_id" required>
                                <option value="">Seleccione un programa</option>
                                <?php foreach ($programas as $p): ?>
                                    <option value="<?= (int)$p['PROGRAMA_ID'] ?>" <?= ($preProg === (int)$p['PROGRAMA_ID']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['NOMBRE']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Centro *</label>
                            <select class="form-control" name="centro_id" required>
                                <option value="">Seleccione un centro</option>
                                <?php foreach ($centros as $c): ?>
                                    <option value="<?= (int)$c['CENTRO_ID'] ?>" <?= ($preCent === (int)$c['CENTRO_ID']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['NOMBRE']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Fecha de inicio *</label>
                            <input type="date" class="form-control" name="fecha_inicio" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-check"></i> Fecha de fin *</label>
                            <input type="date" class="form-control" name="fecha_fin" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-toggle-on"></i> Estado</label>
                            <select class="form-control" name="estado">
                                <option value="Activa">Activa</option>
                                <option value="Inactiva">Inactiva</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <!-- Cancelar: si venimos de un programa, volvemos a programa_detalle.php, si no a programas.php -->
                            <?php if ($programaOrigen && $preProg > 0): ?>
                                <a href="../programa_detalle.php?id=<?= $preProg ?>" class="btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
                            <?php else: ?>
                                <a href="../programas.php" class="btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
                            <?php endif; ?>
                            <button type="submit" class="btn-action"><i class="fas fa-save"></i> Guardar Ficha</button>
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
        document.getElementById('form-ficha').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('guardar_ficha.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Ficha creada!',
                            text: 'Redirigiendo al detalle...',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = '../ficha_detalle.php?id=' + (data.id || '');
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'No se pudo guardar la ficha'
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