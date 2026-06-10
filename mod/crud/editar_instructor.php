<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../instructores.php'); exit; }
require_once __DIR__ . '/../../conexion/InstructorDAO.php';
require_once __DIR__ . '/../../conexion/FichaDAO.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ../instructores.php'); exit; }
$instructorDAO = new InstructorDAO();
$instructor = $instructorDAO->obtenerPorId($id);
if (!$instructor) { header('Location: ../instructores.php'); exit; }

$fichaDAO = new FichaDAO();
$fichas = $fichaDAO->obtenerTodas(); // todas las fichas activas o todas
$fichasAsignadas = $instructorDAO->obtenerFichasIds($id);
$gestorFichaId = (int)($instructor['GESTOR_FICHA_ID'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Instructor - DTD SENA</title>
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
        .form-group-fichas {
            margin-top: 20px;
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }
        .fichas-grid-select {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            max-height: 250px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: var(--color-gris-fondo);
        }
        .ficha-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--color-blanco);
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid var(--border-color);
        }
        .ficha-checkbox:hover {
            background: var(--color-verde-3);
            border-color: var(--color-verde-1);
        }
        .ficha-checkbox input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .ficha-checkbox label {
            flex: 1;
            cursor: pointer;
            font-weight: 500;
            margin: 0;
        }
        .info-asignadas {
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: var(--color-texto-secundario);
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
                <i class="fas fa-user-edit"></i>
                <h2>Editar Instructor</h2>
                <p>Actualice los datos del instructor y sus fichas asignadas</p>
            </div>
            <div class="content-card" style="border-radius: 0 0 20px 20px; margin-top: 0;">
                <div class="card-body">
                    <form id="form-instructor">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Nombres *</label>
                            <input type="text" name="nombres" class="form-control" value="<?= htmlspecialchars($instructor['NOMBRES']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Apellidos *</label>
                            <input type="text" name="apellidos" class="form-control" value="<?= htmlspecialchars($instructor['APELLIDOS']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($instructor['EMAIL']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-code"></i> Especialidad</label>
                            <input type="text" name="especialidad" class="form-control" value="<?= htmlspecialchars($instructor['ESPECIALIDAD'] ?? '') ?>">
                        </div>

                        <!-- SECCIÓN DE FICHAS ASIGNADAS -->
                        <div class="form-group-fichas">
                            <label><i class="fas fa-layer-group"></i> Fichas asignadas</label>
                            <div class="info-asignadas">
                                <i class="fas fa-info-circle"></i> Marque las fichas en las que el instructor imparte clase.
                            </div>
                            <div class="fichas-grid-select" id="fichasContainer">
                                <?php if (empty($fichas)): ?>
                                    <div class="empty-state" style="grid-column:1/-1; padding:20px; text-align:center;">
                                        <i class="fas fa-folder-open"></i> No hay fichas disponibles
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($fichas as $ficha): ?>
                                        <div class="ficha-checkbox">
                                            <input type="checkbox"
                                                   name="fichas[]"
                                                   value="<?= $ficha['FICHA_ID'] ?>"
                                                   id="ficha_<?= $ficha['FICHA_ID'] ?>"
                                                   <?= in_array($ficha['FICHA_ID'], $fichasAsignadas) ? 'checked' : '' ?>
                                                   onchange="actualizarGestor()">
                                            <label for="ficha_<?= $ficha['FICHA_ID'] ?>">
                                                <strong><?= htmlspecialchars($ficha['CODIGO_FICHA']) ?></strong><br>
                                                <small><?= htmlspecialchars($ficha['programa_nombre'] ?? '') ?></small>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group" id="gestorGroup" style="display:none; margin-top:18px;">
                            <label><i class="fas fa-crown"></i> Gestor de Grupo</label>
                            <select name="gestor_ficha_id" id="gestor_ficha_sel" class="form-control">
                                <option value="0">— No es gestor de ninguna ficha —</option>
                            </select>
                            <small style="display:block;margin-top:8px;color:var(--color-texto-secundario);">
                                Selecciona la ficha de la cual este instructor será gestor. Debe ser una ficha en la que esté asignado.
                            </small>
                        </div>

                        <div class="form-actions">
                            <a href="../instructor_detalle.php?id=<?= $id ?>" class="btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
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
        const FICHAS = <?= json_encode($fichas) ?>;
        const GESTOR_FICHA_ID = <?= $gestorFichaId ?>;

        function actualizarGestor() {
            const checks = [...document.querySelectorAll('input[name="fichas[]"]:checked')];
            const gestorSelect = document.getElementById('gestor_ficha_sel');
            const gestorGroup = document.getElementById('gestorGroup');
            gestorSelect.innerHTML = '<option value="0">— No es gestor de ninguna ficha —</option>';

            if (!checks.length) {
                gestorGroup.style.display = 'none';
                return;
            }

            checks.forEach(check => {
                const fichaId = parseInt(check.value, 10);
                const ficha = FICHAS.find(f => parseInt(f.FICHA_ID, 10) === fichaId);
                if (ficha) {
                    const selected = fichaId === GESTOR_FICHA_ID ? ' selected' : '';
                    gestorSelect.innerHTML += `<option value="${fichaId}"${selected}>${ficha.CODIGO_FICHA} — ${ficha.programa_nombre || ''}</option>`;
                }
            });
            gestorGroup.style.display = 'block';
        }

        document.getElementById('form-instructor').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('guardar_instructor.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Instructor actualizado',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = '../instructor_detalle.php?id=<?= $id ?>';
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
        window.addEventListener('DOMContentLoaded', actualizarGestor);
        if (typeof initThemeToggle === 'function') setTimeout(initThemeToggle, 100);
    </script>
</body>
</html>