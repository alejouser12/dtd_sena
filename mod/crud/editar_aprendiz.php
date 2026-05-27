<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../aprendices.php'); exit; }
require_once __DIR__ . '/../../conexion/AprendizDAO.php';
require_once __DIR__ . '/../../conexion/FichaDAO.php';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ../aprendices.php'); exit; }
$ap = (new AprendizDAO())->obtenerPorId($id);
if (!$ap) { header('Location: ../aprendices.php'); exit; }
$fichas = (new FichaDAO())->obtenerTodas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Aprendiz - DTD SENA</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-container {
            max-width: 800px;
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
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: var(--color-texto);
        }
        .form-group i {
            margin-right: 8px;
            color: var(--color-verde-1);
        }
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background: var(--color-blanco);
            color: var(--color-texto);
        }
        .form-control:focus {
            border-color: var(--color-verde-1);
            outline: none;
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
                <i class="fas fa-user-edit"></i>
                <h2>Editar Aprendiz</h2>
                <p>Actualice los datos del aprendiz</p>
            </div>
            <div class="content-card" style="border-radius: 0 0 20px 20px; margin-top: 0;">
                <div class="card-body">
                    <form id="form-aprendiz">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> Tipo documento</label>
                            <select name="tipo_documento" class="form-control">
                                <?php foreach (['CC','TI','CE'] as $t): ?>
                                    <option value="<?= $t ?>" <?= ($ap['TIPO_DOCUMENTO'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-hashtag"></i> Número de documento *</label>
                            <input type="text" name="numero_documento" class="form-control" value="<?= htmlspecialchars($ap['NUMERO_DOCUMENTO'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Nombres *</label>
                            <input type="text" name="nombres" class="form-control" value="<?= htmlspecialchars($ap['NOMBRES'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Apellidos *</label>
                            <input type="text" name="apellidos" class="form-control" value="<?= htmlspecialchars($ap['APELLIDOS'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($ap['EMAIL'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($ap['TELEFONO'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Fecha de nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control" value="<?= htmlspecialchars(substr($ap['FECHA_NACIMIENTO'] ?? '', 0, 10)) ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-venus-mars"></i> Género</label>
                            <input type="text" name="genero" class="form-control" value="<?= htmlspecialchars($ap['GENERO'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-chalkboard-user"></i> Estado académico</label>
                            <input type="text" name="estado_academico" class="form-control" value="<?= htmlspecialchars($ap['ESTADO_ACADEMICO'] ?? 'Activo') ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-layer-group"></i> Ficha</label>
                            <select name="ficha_id" class="form-control">
                                <option value="">Sin ficha</option>
                                <?php foreach ($fichas as $fi): ?>
                                    <option value="<?= (int)$fi['FICHA_ID'] ?>" <?= (int)($ap['FICHA_ID'] ?? 0) === (int)$fi['FICHA_ID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($fi['CODIGO_FICHA']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-actions">
                            <a href="../aprendiz_detalle.php?id=<?= $id ?>" class="btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
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
        document.getElementById('form-aprendiz').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('guardar_aprendiz.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Aprendiz actualizado',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '../aprendiz_detalle.php?id=<?= $id ?>';
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

        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>