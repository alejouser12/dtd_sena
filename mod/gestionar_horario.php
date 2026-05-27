<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
if (!esAdmin() && !esInstructor()) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../conexion/FichaDAO.php';
require_once __DIR__ . '/../conexion/HorarioDAO.php';

$fichaId = isset($_GET['ficha_id']) ? (int)$_GET['ficha_id'] : 0;
if ($fichaId <= 0) {
    header('Location: instructores.php');
    exit;
}
$fichaDAO = new FichaDAO();
$ficha = $fichaDAO->obtenerPorId($fichaId);
if (!$ficha) {
    header('Location: instructores.php');
    exit;
}

$horarioDAO = new HorarioDAO();
$trimestreActual = date('Y') . '-' . ceil(date('m') / 3);
$horario = $horarioDAO->obtenerHorarioFicha($fichaId, $trimestreActual);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Horario - Ficha <?= htmlspecialchars($ficha['CODIGO_FICHA']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .horario-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; flex-wrap: wrap; }
        .dia { width: 100px; }
        .hora { width: 80px; }
        .materia { width: 180px; }
        .aula { width: 80px; }
        .btn-agregar { margin-top: 20px; }
    </style>
</head>
<body>
    <?php include '../config/header.php'; ?>
    <main class="container">
        <a href="instructor_detalle.php?id=<?= $_SESSION['instructor_id'] ?? 1 ?>" class="btn-view-all">Volver</a>
        <div class="content-card">
            <div class="card-header"><h2>Horario - Ficha <?= htmlspecialchars($ficha['CODIGO_FICHA']) ?></h2></div>
            <div class="card-body">
                <form id="formHorario">
                    <input type="hidden" name="ficha_id" value="<?= $fichaId ?>">
                    <div class="form-group">
                        <label>Trimestre (ej: 2025-1, 2025-2)</label>
                        <input type="text" name="trimestre" class="form-control" value="<?= $trimestreActual ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha desde</label>
                        <input type="date" name="fecha_desde" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control" required>
                    </div>
                    <div id="horarios-container">
                        <?php if (!empty($horario)): ?>
                            <?php foreach ($horario as $h): ?>
                            <div class="horario-row">
                                <select name="dia[]" class="dia" required>
                                    <option value="1" <?= $h['DIA_SEMANA']==1?'selected':'' ?>>Lunes</option>
                                    <option value="2" <?= $h['DIA_SEMANA']==2?'selected':'' ?>>Martes</option>
                                    <option value="3" <?= $h['DIA_SEMANA']==3?'selected':'' ?>>Miércoles</option>
                                    <option value="4" <?= $h['DIA_SEMANA']==4?'selected':'' ?>>Jueves</option>
                                    <option value="5" <?= $h['DIA_SEMANA']==5?'selected':'' ?>>Viernes</option>
                                    <option value="6" <?= $h['DIA_SEMANA']==6?'selected':'' ?>>Sábado</option>
                                </select>
                                <input type="time" name="hora_inicio[]" class="hora" value="<?= substr($h['HORA_INICIO'],0,5) ?>" required>
                                <input type="time" name="hora_fin[]" class="hora" value="<?= substr($h['HORA_FIN'],0,5) ?>" required>
                                <input type="text" name="materia[]" class="materia" value="<?= htmlspecialchars($h['MATERIA']) ?>" required>
                                <input type="text" name="aula[]" class="aula" value="<?= htmlspecialchars($h['AULA']) ?>">
                                <button type="button" class="btn-cancel" onclick="this.parentElement.remove()">X</button>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="horario-row">
                                <select name="dia[]" class="dia" required>
                                    <option value="1">Lunes</option><option value="2">Martes</option><option value="3">Miércoles</option>
                                    <option value="4">Jueves</option><option value="5">Viernes</option><option value="6">Sábado</option>
                                </select>
                                <input type="time" name="hora_inicio[]" class="hora" required>
                                <input type="time" name="hora_fin[]" class="hora" required>
                                <input type="text" name="materia[]" class="materia" placeholder="Materia" required>
                                <input type="text" name="aula[]" class="aula" placeholder="Aula">
                                <button type="button" class="btn-cancel" onclick="this.parentElement.remove()">X</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn-create" onclick="agregarFila()"><i class="fas fa-plus"></i> Agregar clase</button>
                    <div class="form-actions">
                        <button type="submit" class="btn-action">Guardar Horario</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include '../config/footer.php'; ?>
    <script>
        function agregarFila() {
            const container = document.getElementById('horarios-container');
            const newRow = document.createElement('div');
            newRow.className = 'horario-row';
            newRow.innerHTML = `
                <select name="dia[]" class="dia" required>
                    <option value="1">Lunes</option><option value="2">Martes</option><option value="3">Miércoles</option>
                    <option value="4">Jueves</option><option value="5">Viernes</option><option value="6">Sábado</option>
                </select>
                <input type="time" name="hora_inicio[]" class="hora" required>
                <input type="time" name="hora_fin[]" class="hora" required>
                <input type="text" name="materia[]" class="materia" placeholder="Materia" required>
                <input type="text" name="aula[]" class="aula" placeholder="Aula">
                <button type="button" class="btn-cancel" onclick="this.parentElement.remove()">X</button>
            `;
            container.appendChild(newRow);
        }

        document.getElementById('formHorario').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('guardar_horario.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        Swal.fire({ icon: 'success', title: 'Horario guardado', timer: 1500, showConfirmButton: false })
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: d.message });
                    }
                });
        });
    </script>
</body>
</html>