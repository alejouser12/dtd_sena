<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../programas.php'); exit; }
require_once __DIR__ . '/../../conexion/CentroDAO.php';

$centroDAO = new CentroDAO();
$centros = $centroDAO->obtenerTodos();

// Obtener el centro desde la URL (para saber a dónde volver)
$centroId = isset($_GET['centro_id']) ? (int)$_GET['centro_id'] : 0;

// Validar que el centro exista (para evitar enlaces rotos)
$centro = null;
if ($centroId > 0) {
    $centro = $centroDAO->obtenerPorId($centroId);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Programa - DTD SENA</title>
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
                <i class="fas fa-plus-circle"></i>
                <h2>Nuevo Programa</h2>
                <p>Registre un programa de formación</p>
            </div>
            <div class="content-card" style="border-radius: 0 0 20px 20px; margin-top: 0;">
                <div class="card-body">
                    <form id="form-programa">
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Centro *</label>
                            <select name="centro_id" class="form-control" required>
                                <option value="">Seleccione un centro</option>
                                <?php foreach ($centros as $c): ?>
                                    <option value="<?= $c['CENTRO_ID'] ?>" <?= ($centroId === (int)$c['CENTRO_ID']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['NOMBRE']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Nombre *</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Análisis y Desarrollo de Software" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-graduation-cap"></i> Nivel *</label>
                            <input type="text" name="nivel_formacion" class="form-control" placeholder="Técnico / Tecnólogo" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Duración (meses)</label>
                            <input type="number" name="duracion_meses" class="form-control" value="12" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-toggle-on"></i> Estado</label>
                            <select name="estado" class="form-control">
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <!-- Botón Cancelar: vuelve al centro si se conoce, si no a programas.php -->
                            <?php if ($centro && $centroId > 0): ?>
                                <a href="../centro_detalle.php?id=<?= $centroId ?>" class="btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
                            <?php else: ?>
                                <a href="../programas.php" class="btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
                            <?php endif; ?>
                            <button type="submit" class="btn-action"><i class="fas fa-save"></i> Guardar Programa</button>
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
        document.getElementById('form-programa').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('guardar_programa.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Programa creado',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            // Redirige a la página de detalle del programa recién creado
                            window.location.href = '../programa_detalle.php?id=' + data.id;
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