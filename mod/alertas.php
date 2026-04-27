<?php
// mod/alertas.php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/AlertaDAO.php';
require_once __DIR__ . '/../conexion/AprendizDAO.php';

$alertaDAO = new AlertaDAO();
$aprendizDAO = new AprendizDAO();

// Obtener alertas activas
$alertas = $alertaDAO->obtenerAlertasActivas();

// Obtener conteo
$conteo = $alertaDAO->obtenerConteoAlertas();

// Obtener aprendices para el selector
$aprendices = $aprendizDAO->obtenerAprendicesPaginados(0, 100);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertas - DTD SENA</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .alertas-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .btn-nueva-alerta {
            background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2));
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(57,169,0,0.3);
        }
        
        .btn-nueva-alerta:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(57,169,0,0.4);
        }
        
        .alertas-resumen {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .resumen-card {
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease-out forwards;
            opacity: 0;
        }
        
        .resumen-card:nth-child(1) { animation-delay: 0.1s; }
        .resumen-card:nth-child(2) { animation-delay: 0.2s; }
        .resumen-card:nth-child(3) { animation-delay: 0.3s; }
        .resumen-card:nth-child(4) { animation-delay: 0.4s; }
        
        .resumen-card.total { border-left: 4px solid var(--color-verde-1); }
        .resumen-card.alta { border-left: 4px solid #dc2626; }
        .resumen-card.media { border-left: 4px solid #f59e0b; }
        .resumen-card.baja { border-left: 4px solid var(--color-verde-1); }
        
        .resumen-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .total .resumen-icon {
            background: rgba(57,169,0,0.1);
            color: var(--color-verde-1);
        }
        
        .alta .resumen-icon {
            background: rgba(220,38,38,0.1);
            color: #dc2626;
        }
        
        .media .resumen-icon {
            background: rgba(245,158,11,0.1);
            color: #f59e0b;
        }
        
        .baja .resumen-icon {
            background: rgba(57,169,0,0.1);
            color: var(--color-verde-1);
        }
        
        .resumen-valor {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-texto);
            line-height: 1.2;
        }
        
        .resumen-label {
            font-size: 13px;
            color: var(--color-texto-secundario);
        }
        
        .alertas-lista {
            display: grid;
            gap: 15px;
            margin-top: 30px;
        }
        
        .alerta-item {
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            padding: 20px;
            display: flex;
            gap: 20px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-color);
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
            animation: slideInRight 0.5s ease-out forwards;
            opacity: 0;
        }
        
        .alerta-item.alta { border-left-color: #dc2626; }
        .alerta-item.media { border-left-color: #f59e0b; }
        .alerta-item.baja { border-left-color: var(--color-verde-1); }
        
        .alerta-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .alerta-item.alta .alerta-icon {
            background: rgba(220,38,38,0.1);
            color: #dc2626;
        }
        
        .alerta-item.media .alerta-icon {
            background: rgba(245,158,11,0.1);
            color: #f59e0b;
        }
        
        .alerta-item.baja .alerta-icon {
            background: rgba(57,169,0,0.1);
            color: var(--color-verde-1);
        }
        
        .alerta-content {
            flex: 1;
        }
        
        .alerta-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .alerta-header h3 {
            font-size: 18px;
            margin: 0;
        }
        
        .alerta-header h3 a {
            color: var(--color-texto);
            text-decoration: none;
            font-weight: 600;
        }
        
        .alerta-header h3 a:hover {
            color: var(--color-verde-1);
        }
        
        .alerta-nivel {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
        }
        
        .alerta-item.alta .alerta-nivel {
            background: rgba(220,38,38,0.1);
            color: #dc2626;
        }
        
        .alerta-item.media .alerta-nivel {
            background: rgba(245,158,11,0.1);
            color: #f59e0b;
        }
        
        .alerta-item.baja .alerta-nivel {
            background: rgba(57,169,0,0.1);
            color: var(--color-verde-1);
        }
        
        .alerta-descripcion {
            color: var(--color-texto);
            margin-bottom: 10px;
            line-height: 1.5;
        }
        
        .alerta-meta {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: var(--color-texto-secundario);
            flex-wrap: wrap;
        }
        
        .alerta-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .alerta-meta i {
            color: var(--color-verde-1);
        }
        
        .alerta-accion {
            display: flex;
            align-items: center;
        }
        
        .btn-marcar-leida {
            background: transparent;
            border: 2px solid var(--color-verde-1);
            color: var(--color-verde-1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-marcar-leida:hover {
            background: var(--color-verde-1);
            color: white;
            transform: scale(1.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            border: 2px dashed var(--border-color);
            grid-column: 1/-1;
        }
        
        .empty-state i {
            font-size: 60px;
            color: var(--color-verde-1);
            opacity: 0.3;
            margin-bottom: 15px;
        }
        
        .empty-state h3 {
            font-size: 24px;
            color: var(--color-texto);
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: var(--color-texto-secundario);
        }
        
        /* Modal */
        .modal {
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background: var(--color-blanco);
            margin: 50px auto;
            padding: 30px;
            border-radius: var(--border-radius-card);
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid var(--color-verde-1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .modal-header h2 {
            font-size: 24px;
            color: var(--color-verde-1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-texto-secundario);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .close:hover {
            color: var(--color-rojo-1);
        }
        
        /* Buscador de aprendices */
        .buscador-aprendices {
            margin-bottom: 20px;
        }
        
        .buscador-aprendices input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-input);
            font-size: 15px;
        }
        
        .buscador-aprendices input:focus {
            border-color: var(--color-verde-1);
            outline: none;
        }
        
        .lista-aprendices {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .aprendiz-item {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .aprendiz-item:last-child {
            border-bottom: none;
        }
        
        .aprendiz-item:hover {
            background: var(--color-verde-3);
        }
        
        .aprendiz-item.seleccionado {
            background: var(--color-verde-4);
            border-left: 4px solid var(--color-verde-1);
        }
        
        .aprendiz-nombre {
            font-weight: 600;
            color: var(--color-texto);
        }
        
        .aprendiz-ficha {
            font-size: 12px;
            color: var(--color-texto-secundario);
        }
        
        .aprendiz-info small {
            display: block;
            font-size: 11px;
            color: var(--color-verde-1);
        }
        
        /* Formulario */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--color-texto);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-input);
            font-size: 14px;
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
            margin-top: 25px;
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* Modo oscuro */
        .dark-mode .resumen-card,
        .dark-mode .alerta-item,
        .dark-mode .empty-state,
        .dark-mode .modal-content {
            background: var(--color-gris-cuerpo);
        }
        
        .dark-mode .aprendiz-item:hover {
            background: var(--color-gris-fondo);
        }
        
        .dark-mode .aprendiz-item.seleccionado {
            background: var(--color-verde-4);
        }
        
        @media (max-width: 768px) {
            .alertas-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .alertas-resumen {
                grid-template-columns: 1fr 1fr;
            }
            
            .alerta-item {
                flex-direction: column;
            }
            
            .alerta-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .alerta-meta {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div id="loader">
        <img src="<?= htmlspecialchars(asset_url('img/logo_sena_verde.png')) ?>" alt="Logo SENA" id="loader-logo">
    </div>

    <!-- HEADER incluido directamente -->
    <?php include __DIR__ . '/../config/header.php'; ?>

    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div class="alertas-header">
            <div class="page-header">
                <h1 class="page-title">Alertas de Deserción</h1>
                <p class="page-subtitle">Monitoreo y gestión de aprendices en riesgo</p>
            </div>
            <button class="btn-nueva-alerta" onclick="abrirModalNuevaAlerta()">
                <i class="fas fa-plus-circle"></i> Nueva Alerta
            </button>
        </div>

        <!-- Tarjetas de resumen -->
        <div class="alertas-resumen">
            <div class="resumen-card total">
                <div class="resumen-icon"><i class="fas fa-bell"></i></div>
                <div>
                    <span class="resumen-valor"><?php echo $conteo['total'] ?? 0; ?></span>
                    <span class="resumen-label">Total Alertas</span>
                </div>
            </div>
            <div class="resumen-card alta">
                <div class="resumen-icon"><i class="fas fa-exclamation-circle"></i></div>
                <div>
                    <span class="resumen-valor"><?php echo $conteo['altas'] ?? 0; ?></span>
                    <span class="resumen-label">Riesgo Alto</span>
                </div>
            </div>
            <div class="resumen-card media">
                <div class="resumen-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div>
                    <span class="resumen-valor"><?php echo $conteo['medias'] ?? 0; ?></span>
                    <span class="resumen-label">Riesgo Medio</span>
                </div>
            </div>
            <div class="resumen-card baja">
                <div class="resumen-icon"><i class="fas fa-info-circle"></i></div>
                <div>
                    <span class="resumen-valor"><?php echo $conteo['bajas'] ?? 0; ?></span>
                    <span class="resumen-label">Riesgo Bajo</span>
                </div>
            </div>
        </div>

        <!-- Lista de alertas -->
        <div class="alertas-lista">
            <?php if (empty($alertas)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>¡Todo tranquilo!</h3>
                    <p>No hay alertas activas en este momento.</p>
                </div>
            <?php else: ?>
                <?php foreach($alertas as $alerta): 
                    $claseNivel = strtolower($alerta['NIVEL']);
                    $icono = $claseNivel == 'alto' ? 'fa-exclamation-circle' : 
                            ($claseNivel == 'medio' ? 'fa-exclamation-triangle' : 'fa-info-circle');
                ?>
                <div class="alerta-item <?php echo $claseNivel; ?>" id="alerta-<?php echo $alerta['ALERTA_ID']; ?>">
                    <div class="alerta-icon">
                        <i class="fas <?php echo $icono; ?>"></i>
                    </div>
                    <div class="alerta-content">
                        <div class="alerta-header">
                            <h3>
                                <a href="<?= htmlspecialchars(app_url('mod/aprendiz_detalle.php?id=' . (int)$alerta['APRENDIZ_ID'])) ?>">
                                    <?php echo htmlspecialchars($alerta['aprendiz_nombres'] . ' ' . $alerta['aprendiz_apellidos']); ?>
                                </a>
                            </h3>
                            <span class="alerta-nivel"><?php echo htmlspecialchars($alerta['NIVEL']); ?></span>
                        </div>
                        <p class="alerta-descripcion"><?php echo htmlspecialchars($alerta['DESCRIPCION']); ?></p>
                        <div class="alerta-meta">
                            <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($alerta['TIPO_OBSERVACION'] ?? $alerta['TIPO_REGLA'] ?? 'Seguimiento'); ?></span>
                            <span><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars(trim(($alerta['instructor_nombres'] ?? 'Sistema') . ' ' . ($alerta['instructor_apellidos'] ?? ''))); ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($alerta['FECHA_GENERACION'])); ?></span>
                        </div>
                    </div>
                    <div class="alerta-accion">
                        <button class="btn-marcar-leida" onclick="marcarLeida(<?php echo $alerta['ALERTA_ID']; ?>)">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de una nueva alerta -->
    <div id="modalNuevaAlerta" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Nueva Alerta</h2>
                <span class="close" onclick="cerrarModalNuevaAlerta()">&times;</span>
            </div>
            
            <div class="buscador-aprendices">
                <input type="text" id="buscadorAprendiz" placeholder="Buscar aprendiz por nombre, documento o ficha..." onkeyup="filtrarAprendices()">
            </div>
            
            <div class="lista-aprendices" id="listaAprendices">
                <?php foreach($aprendices as $ap): ?>
                <div class="aprendiz-item" data-id="<?php echo $ap['APRENDIZ_ID']; ?>" data-nombre="<?php echo strtolower($ap['NOMBRES'] . ' ' . $ap['APELLIDOS']); ?>" data-documento="<?php echo $ap['NUMERO_DOCUMENTO']; ?>" data-ficha="<?php echo $ap['CODIGO_FICHA'] ?? ''; ?>" onclick="seleccionarAprendiz(this, <?php echo $ap['APRENDIZ_ID']; ?>, '<?php echo $ap['NOMBRES'] . ' ' . $ap['APELLIDOS']; ?>')">
                    <div>
                        <div class="aprendiz-nombre"><?php echo $ap['NOMBRES'] . ' ' . $ap['APELLIDOS']; ?></div>
                        <div class="aprendiz-info">
                            <small><?php echo $ap['TIPO_DOCUMENTO'] . ' ' . $ap['NUMERO_DOCUMENTO']; ?></small>
                            <?php if(!empty($ap['CODIGO_FICHA'])): ?>
                                <span class="aprendiz-ficha">Ficha: <?php echo $ap['CODIGO_FICHA']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="color: var(--color-verde-1);"></i>
                </div>
                <?php endforeach; ?>
            </div>
            
            <form id="formNuevaAlerta" onsubmit="guardarAlerta(event)">
                <input type="hidden" name="estudiante_id" id="estudiante_id" value="">
                <input type="hidden" name="instructor_id" value="<?= (int)($_SESSION['usuario_ref_id'] ?? 0) ?>">
                
                <div class="form-group">
                    <label for="tipo">Tipo de Alerta *</label>
                    <select name="tipo" id="tipo" class="form-control" required>
                        <option value="">Seleccione tipo</option>
                        <option value="Académica">Académica</option>
                        <option value="Disciplinaria">Disciplinaria</option>
                        <option value="Actitudinal">Actitudinal</option>
                        <option value="Asistencia">Asistencia</option>
                        <option value="Familiar">Familiar</option>
                        <option value="Salud">Salud</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="nivel_riesgo">Nivel de Riesgo *</label>
                    <select name="nivel_riesgo" id="nivel_riesgo" class="form-control" required>
                        <option value="">Seleccione nivel</option>
                        <option value="Bajo">Bajo</option>
                        <option value="Medio">Medio</option>
                        <option value="Alto">Alto</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción *</label>
                    <textarea name="descripcion" id="descripcion" 
                    rows="4" class="form-control" placeholder="Describa el motivo de la alerta..." required></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="cerrarModalNuevaAlerta()">Cancelar</button>
                    <button type="submit" class="btn-action">Guardar Alerta</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FOOTER incluido directamente -->
    <?php include __DIR__ . '/../config/footer.php'; ?>

    <script src="<?= htmlspecialchars(asset_url('js/tema.js')) ?>"></script>
    <script src="<?= htmlspecialchars(asset_url('js/loader.js')) ?>"></script>
    <script src="<?= htmlspecialchars(asset_url('js/panel_menu.js')) ?>"></script>
    <script src="<?= htmlspecialchars(asset_url('js/dropdowns.js')) ?>"></script>
    <script src="<?= htmlspecialchars(asset_url('js/profile_menu.js')) ?>"></script>
    <script src="<?= htmlspecialchars(asset_url('js/sweetalerts.js')) ?>"></script>
    <script src="<?= htmlspecialchars(asset_url('js/menu.js')) ?>"></script>
    
    <script>
        let aprendizSeleccionadoId = null;
        let aprendizSeleccionadoNombre = '';
        
        function abrirModalNuevaAlerta() {
            document.getElementById('modalNuevaAlerta').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function cerrarModalNuevaAlerta() {
            document.getElementById('modalNuevaAlerta').style.display = 'none';
            document.body.style.overflow = '';
            resetFormulario();
        }
        
        function resetFormulario() {
            document.getElementById('estudiante_id').value = '';
            document.getElementById('tipo').value = '';
            document.getElementById('nivel_riesgo').value = '';
            document.getElementById('descripcion').value = '';
            aprendizSeleccionadoId = null;
            
            document.querySelectorAll('.aprendiz-item').forEach(item => {
                item.classList.remove('seleccionado');
            });
        }
        
        function filtrarAprendices() {
            let filtro = document.getElementById('buscadorAprendiz').value.toLowerCase();
            let items = document.querySelectorAll('.aprendiz-item');
            
            items.forEach(item => {
                let nombre = item.dataset.nombre;
                let documento = item.dataset.documento;
                let ficha = item.dataset.ficha;
                
                if (nombre.includes(filtro) || documento.includes(filtro) || ficha.includes(filtro)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function seleccionarAprendiz(elemento, id, nombre) {
            document.querySelectorAll('.aprendiz-item').forEach(item => {
                item.classList.remove('seleccionado');
            });
            
            elemento.classList.add('seleccionado');
            
            document.getElementById('estudiante_id').value = id;
            aprendizSeleccionadoId = id;
            aprendizSeleccionadoNombre = nombre;
        }
        
        function guardarAlerta(event) {
            event.preventDefault();
            
            if (!aprendizSeleccionadoId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Seleccione un aprendiz',
                    text: 'Debe seleccionar un aprendiz para crear la alerta'
                });
                return;
            }
            
            let formData = new FormData(document.getElementById('formNuevaAlerta'));
            let submitBtn = event.submitter;
            let originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            submitBtn.disabled = true;
            
            fetch('guardar_alerta.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cerrarModalNuevaAlerta();
                    Swal.fire({
                        icon: 'success',
                        title: '¡Alerta creada!',
                        text: 'La alerta se ha guardado correctamente',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al guardar'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al conectar con el servidor'
                });
            });
        }
        
        function marcarLeida(id) {
            fetch('marcar_notificacion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalNuevaAlerta();
            }
        });
        
        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>
