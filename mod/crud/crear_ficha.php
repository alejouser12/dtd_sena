<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../programas.php'); exit; }
require_once __DIR__ . '/../../conexion/ProgramaDAO.php';
require_once __DIR__ . '/../../conexion/CentroDAO.php';
require_once __DIR__ . '/../../conexion/conexion.php';

$programaDAO = new ProgramaDAO();
$centroDAO   = new CentroDAO();

$programas = $programaDAO->obtenerProgramas();
$centros   = $centroDAO->obtenerTodos();

// Cargar instructores
class FichaInstrDB extends BaseDatos {
    protected function consultar(){}protected function insertar(){}
    protected function actualizar(){}protected function eliminar(){}
    public function varios($sql,$p=[]){ $s=$this->ejecutarPreparado($sql,$p); return $s?$s->fetchAll(PDO::FETCH_ASSOC):[]; }
}
$db = new FichaInstrDB();
$instructores = $db->varios(
    "SELECT INSTRUCTOR_ID, NOMBRES, APELLIDOS, ESPECIALIDAD
     FROM instructor ORDER BY NOMBRES, APELLIDOS"
);

$preProg = isset($_GET['programa_id']) ? (int)$_GET['programa_id'] : 0;
$preCent = isset($_GET['centro_id'])   ? (int)$_GET['centro_id']   : 0;
$programaOrigen = ($preProg > 0) ? $programaDAO->obtenerPorId($preProg) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Ficha — DTD SENA</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-container { max-width: 760px; margin: 0 auto; }
        .form-header {
            background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2));
            padding: 25px; border-radius: 20px 20px 0 0;
            color: white; text-align: center;
        }
        .form-header i   { font-size: 50px; margin-bottom: 10px; display: block; }
        .form-header h2  { margin: 0; font-size: 28px; }
        .form-header p   { margin: 5px 0 0; opacity: .9; }
        .card-body       { padding: 30px; }
        .form-actions    { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; }
        .section-sep     { border: none; border-top: 2px solid rgba(57,169,0,.12); margin: 24px 0 20px; }
        .section-label   { font-size: 12px; font-weight: 800; color: var(--color-verde-1); text-transform: uppercase;
                           letter-spacing: .5px; margin-bottom: 12px; display: flex; align-items: center; gap: 7px; }
        /* Instructor list */
        .inst-search     { width: 100%; padding: 9px 12px; border: 1px solid var(--border-color);
                           border-radius: 8px; font-size: 13px; margin-bottom: 10px;
                           background: var(--color-blanco); color: var(--color-texto); }
        .inst-search:focus { outline: none; border-color: var(--color-verde-1); }
        .inst-list       { border: 1px solid var(--border-color); border-radius: 10px;
                           max-height: 220px; overflow-y: auto; background: var(--color-blanco); }
        .inst-item       { display: flex; align-items: center; gap: 10px; padding: 9px 14px;
                           border-bottom: 1px solid rgba(57,169,0,.06); cursor: pointer;
                           transition: background .15s; }
        .inst-item:last-child { border-bottom: none; }
        .inst-item:hover { background: rgba(57,169,0,.05); }
        .inst-item input[type=radio] { accent-color: var(--color-verde-1); width: 15px; height: 15px; flex-shrink: 0; }
        .inst-name       { font-size: 13px; font-weight: 600; color: var(--color-texto); }
        .inst-espec      { font-size: 11px; color: var(--color-texto-secundario); }
        .inst-none-msg   { padding: 14px; text-align: center; color: var(--color-texto-secundario);
                           font-size: 12px; display: none; }
        @media (max-width: 768px) {
            .form-actions { flex-direction: column; }
            .form-actions .btn-cancel, .form-actions .btn-action { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
<div id="loader"><img src="../../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo"></div>
<?php include __DIR__ . '/../../config/header.php'; ?>
<main class="container" id="contenido-principal" style="display:none;opacity:0;">
<div class="form-container">
    <div class="form-header">
        <i class="fas fa-layer-group"></i>
        <h2>Nueva Ficha</h2>
        <p>Registra una nueva ficha de formación</p>
    </div>
    <div class="content-card" style="border-radius:0 0 20px 20px;margin-top:0;">
        <div class="card-body">
        <form id="form-ficha">

            <!-- Datos básicos -->
            <div class="form-group">
                <label><i class="fas fa-barcode"></i> Código de ficha *</label>
                <input type="text" class="form-control" name="codigo_ficha" placeholder="Ej: ADS2568914" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-graduation-cap"></i> Programa *</label>
                <select class="form-control" name="programa_id" required>
                    <option value="">Seleccione un programa</option>
                    <?php foreach ($programas as $p): ?>
                    <option value="<?= (int)$p['PROGRAMA_ID'] ?>" <?= $preProg===(int)$p['PROGRAMA_ID']?'selected':'' ?>>
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
                    <option value="<?= (int)$c['CENTRO_ID'] ?>" <?= $preCent===(int)$c['CENTRO_ID']?'selected':'' ?>>
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

            <hr class="section-sep">

            <!-- Gestor de grupo -->
            <div class="section-label">
                <i class="fas fa-crown" style="color:#f59e0b;"></i>
                Gestor de Grupo <span style="font-weight:400;color:var(--color-texto-secundario);text-transform:none;font-size:11px;">(opcional — puede asignarse después)</span>
            </div>

            <input type="hidden" name="gestor_instructor_id" id="gestor_instructor_id" value="0">

            <?php if (empty($instructores)): ?>
            <p style="color:var(--color-texto-secundario);font-size:13px;">No hay instructores registrados aún.</p>
            <?php else: ?>
            <input class="inst-search" type="text" placeholder="Buscar instructor por nombre..." id="inst-search" oninput="filtrarInst()">
            <div class="inst-list" id="inst-list">
                <!-- Opción ninguno -->
                <label class="inst-item" id="inst-ninguno">
                    <input type="radio" name="_gestor_radio" value="0" checked onchange="setGestor(0)">
                    <div>
                        <div class="inst-name" style="color:var(--color-texto-secundario);">— Sin gestor asignado —</div>
                    </div>
                </label>
                <?php foreach ($instructores as $i): ?>
                <label class="inst-item inst-row" data-search="<?= strtolower(htmlspecialchars($i['NOMBRES'].' '.$i['APELLIDOS'].' '.($i['ESPECIALIDAD']??''))) ?>">
                    <input type="radio" name="_gestor_radio" value="<?= $i['INSTRUCTOR_ID'] ?>" onchange="setGestor(<?= $i['INSTRUCTOR_ID'] ?>)">
                    <div>
                        <div class="inst-name"><?= htmlspecialchars($i['NOMBRES'].' '.$i['APELLIDOS']) ?></div>
                        <?php if (!empty($i['ESPECIALIDAD'])): ?>
                        <div class="inst-espec"><?= htmlspecialchars($i['ESPECIALIDAD']) ?></div>
                        <?php endif; ?>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            <p class="inst-none-msg" id="inst-none-msg">Sin resultados para tu búsqueda.</p>
            <p style="font-size:11px;color:var(--color-texto-secundario);margin-top:8px;">
                <i class="fas fa-info-circle"></i>
                El gestor podrá crear y editar el horario de esta ficha. Solo puede haber uno por ficha.
            </p>
            <?php endif; ?>

            <div class="form-actions">
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
function setGestor(id) {
    document.getElementById('gestor_instructor_id').value = id;
}
function filtrarInst() {
    const q    = document.getElementById('inst-search').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.inst-row');
    let vis = 0;
    rows.forEach(r => {
        const match = r.dataset.search.includes(q);
        r.style.display = match ? '' : 'none';
        if (match) vis++;
    });
    document.getElementById('inst-none-msg').style.display = vis===0 && q ? 'block' : 'none';
    document.getElementById('inst-ninguno').style.display = q ? 'none' : '';
}

document.getElementById('form-ficha').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('guardar_ficha.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon:'success', title:'¡Ficha creada!', text:'Redirigiendo al detalle...', timer:1500, showConfirmButton:false })
                    .then(() => window.location.href = '../ficha_detalle.php?id=' + (data.id || ''));
            } else {
                Swal.fire({ icon:'error', title:'Error', text: data.message || 'No se pudo guardar la ficha' });
            }
        })
        .catch(() => Swal.fire({ icon:'error', title:'Error de red', text:'No se pudo conectar con el servidor' }));
});
if (typeof initThemeToggle === 'function') setTimeout(initThemeToggle, 100);
</script>
</body>
</html>