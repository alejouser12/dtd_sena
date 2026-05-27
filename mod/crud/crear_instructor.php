<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../instructores.php'); exit; }
require_once __DIR__ . '/../../conexion/RegionalDAO.php';
require_once __DIR__ . '/../../conexion/CentroDAO.php';

$regionalDAO = new RegionalDAO();
$centroDAO = new CentroDAO();

$regionales = $regionalDAO->obtenerTodas(); // todas las regionales activas
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Instructor - DTD SENA</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-container { max-width: 800px; margin: 0 auto; }
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
        .select-fichas {
            min-height: 120px;
            padding: 10px;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-input);
            background: var(--color-blanco);
            color: var(--color-texto);
        }
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
                <i class="fas fa-chalkboard-teacher"></i>
                <h2>Nuevo Instructor</h2>
                <p>Registre un nuevo instructor y asígnele regional, centro y fichas</p>
            </div>
            <div class="content-card" style="border-radius: 0 0 20px 20px; margin-top: 0;">
                <div class="card-body">
                    <form id="form-instructor">
                        <!-- Datos personales -->
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Nombres *</label>
                            <input type="text" name="nombres" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Apellidos *</label>
                            <input type="text" name="apellidos" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-code"></i> Especialidad</label>
                            <input type="text" name="especialidad" class="form-control" placeholder="Ej: Desarrollo de Software">
                        </div>

                        <!-- Ubicación Regional -->
                        <div class="form-group">
                            <label><i class="fas fa-globe-americas"></i> Regional *</label>
                            <select name="regional_id" id="regional_id" class="form-control" required>
                                <option value="">-- Seleccione una regional --</option>
                                <?php foreach ($regionales as $reg): ?>
                                    <option value="<?= $reg['REGIONAL_ID'] ?>"><?= htmlspecialchars($reg['NOMBRE']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Centro (dependiente de regional) -->
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Centro *</label>
                            <select name="centro_id" id="centro_id" class="form-control" required disabled>
                                <option value="">-- Primero seleccione una regional --</option>
                            </select>
                        </div>

                        <!-- Fichas (dependientes del centro) -->
                        <div class="form-group">
                            <label><i class="fas fa-layer-group"></i> Fichas asignadas (puede seleccionar varias)</label>
                            <select name="fichas_ids[]" id="fichas_ids" class="select-fichas" multiple disabled>
                                <option value="">-- Primero seleccione un centro --</option>
                            </select>
                            <small class="form-text text-muted">Mantenga presionada la tecla Ctrl (Cmd en Mac) para seleccionar múltiples fichas.</small>
                        </div>

                        <div class="form-actions">
                            <a href="../instructores.php" class="btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
                            <button type="submit" class="btn-action"><i class="fas fa-save"></i> Guardar Instructor</button>
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
        // Cargar centros según regional seleccionada
        document.getElementById('regional_id').addEventListener('change', function() {
            const regionalId = this.value;
            const centroSelect = document.getElementById('centro_id');
            const fichasSelect = document.getElementById('fichas_ids');

            if (!regionalId) {
                centroSelect.innerHTML = '<option value="">-- Primero seleccione una regional --</option>';
                centroSelect.disabled = true;
                fichasSelect.innerHTML = '<option value="">-- Primero seleccione un centro --</option>';
                fichasSelect.disabled = true;
                return;
            }

            centroSelect.disabled = true;
            centroSelect.innerHTML = '<option value="">Cargando centros...</option>';

            fetch(`../ajax/centros_por_regional.php?regional_id=${regionalId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        centroSelect.innerHTML = '<option value="">Error al cargar centros</option>';
                        centroSelect.disabled = true;
                        return;
                    }
                    if (data.length === 0) {
                        centroSelect.innerHTML = '<option value="">No hay centros en esta regional</option>';
                        centroSelect.disabled = true;
                    } else {
                        let options = '<option value="">-- Seleccione un centro --</option>';
                        data.forEach(centro => {
                            options += `<option value="${centro.CENTRO_ID}">${centro.NOMBRE}</option>`;
                        });
                        centroSelect.innerHTML = options;
                        centroSelect.disabled = false;
                    }
                    // Reset fichas
                    fichasSelect.innerHTML = '<option value="">-- Primero seleccione un centro --</option>';
                    fichasSelect.disabled = true;
                })
                .catch(() => {
                    centroSelect.innerHTML = '<option value="">Error de red</option>';
                    centroSelect.disabled = true;
                });
        });

        // Cargar fichas según centro seleccionado
        document.getElementById('centro_id').addEventListener('change', function() {
            const centroId = this.value;
            const fichasSelect = document.getElementById('fichas_ids');

            if (!centroId) {
                fichasSelect.innerHTML = '<option value="">-- Primero seleccione un centro --</option>';
                fichasSelect.disabled = true;
                return;
            }

            fichasSelect.disabled = true;
            fichasSelect.innerHTML = '<option value="">Cargando fichas...</option>';

            fetch(`../ajax/fichas_por_centro.php?centro_id=${centroId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        fichasSelect.innerHTML = '<option value="">Error al cargar fichas</option>';
                        fichasSelect.disabled = true;
                        return;
                    }
                    if (data.length === 0) {
                        fichasSelect.innerHTML = '<option value="">No hay fichas en este centro</option>';
                        fichasSelect.disabled = true;
                    } else {
                        let options = '';
                        data.forEach(ficha => {
                            options += `<option value="${ficha.FICHA_ID}">${ficha.CODIGO_FICHA} - ${ficha.programa_nombre || ''}</option>`;
                        });
                        fichasSelect.innerHTML = options;
                        fichasSelect.disabled = false;
                    }
                })
                .catch(() => {
                    fichasSelect.innerHTML = '<option value="">Error de red</option>';
                    fichasSelect.disabled = true;
                });
        });

        // Envío del formulario
        document.getElementById('form-instructor').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('guardar_instructor.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        Swal.fire({ icon: 'success', title: 'Instructor creado', timer: 1500, showConfirmButton: false })
                            .then(() => location.href = '../instructor_detalle.php?id=' + d.id);
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