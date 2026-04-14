<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . "/../conexion/AprendizDAO.php";

$aprendizDAO = new AprendizDAO();

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registros_por_pagina = isset($_GET['registros']) ? (int)$_GET['registros'] : 10;
$columna = isset($_GET['columna']) ? $_GET['columna'] : '';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_fichas = isset($_GET['filtro_fichas']) ? $_GET['filtro_fichas'] == '1' : false;

$registros_permitidos = [10, 50, 100];
if (!in_array($registros_por_pagina, $registros_permitidos)) {
    $registros_por_pagina = 10;
}

$inicio = ($pagina - 1) * $registros_por_pagina;

if (!empty($columna) && !empty($busqueda)) {
    $aprendices = $aprendizDAO->buscarPorColumnaPaginado($columna, $busqueda, $inicio, $registros_por_pagina);
    $total_registros = $aprendizDAO->contarBusqueda($columna, $busqueda);
} else {
    $aprendices = $aprendizDAO->obtenerAprendicesPaginados($inicio, $registros_por_pagina, $filtro_fichas);
    $total_registros = $aprendizDAO->contarAprendices($filtro_fichas);
}

$total_paginas = ceil($total_registros / $registros_por_pagina);

$url_params = [];
if (!empty($columna)) $url_params['columna'] = $columna;
if (!empty($busqueda)) $url_params['busqueda'] = $busqueda;
if ($filtro_fichas) $url_params['filtro_fichas'] = '1';
$url_params['registros'] = $registros_por_pagina;
$url_base = 'aprendices.php?' . http_build_query($url_params);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Aprendices</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Estilos para los botones de acción */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        .action-buttons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .action-buttons .btn-view-all {
            background: var(--color-verde-3);
            color: var(--color-verde-1);
        }
        .action-buttons .btn-edit {
            background: rgba(59, 130, 246, 0.1);
            color: var(--color-azul-1);
            border: 1px solid transparent;
        }
        .action-buttons .btn-edit:hover {
            background: var(--color-azul-1);
            color: white;
            transform: translateY(-2px);
        }
        .action-buttons .btn-delete {
            background: rgba(220, 38, 38, 0.1);
            color: var(--color-rojo-1);
            border: 1px solid transparent;
        }
        .action-buttons .btn-delete:hover {
            background: var(--color-rojo-1);
            color: white;
            transform: translateY(-2px);
        }
        /* Ajuste para el colspan cuando no hay acciones */
        .empty-state[colspan="10"] {
            text-align: center;
        }
        .empty-state[colspan="11"] {
            text-align: center;
        }
    </style>
</head>
<body>

<div id="loader">
    <img src="../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo">
</div>

<?php include "../config/header.php"; ?>

<main class="container" id="contenido-principal" style="display:none; opacity:0;">

    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-user-graduate"></i> Gestión de Aprendices
        </h1>
        <p class="page-subtitle">Listado completo de aprendices registrados</p>
    </div>

    <?php if (!empty($busqueda)): ?>
    <div class="info-bar">
        <span>
            <i class="fas fa-search"></i> 
            Buscando en <strong><?= htmlspecialchars($columna) ?></strong>: "<?= htmlspecialchars($busqueda) ?>"
        </span>
        <a href="aprendices.php" class="clear-filter">
            <i class="fas fa-times"></i> Limpiar filtro
        </a>
    </div>
    <?php endif; ?>

    <div class="table-controls">
        <div class="records-per-page">
            <label for="registros">Mostrar:</label>
            <select id="registros" class="records-select" onchange="cambiarRegistros(this.value)">
                <option value="10" <?= $registros_por_pagina == 10 ? 'selected' : '' ?>>10 registros</option>
                <option value="50" <?= $registros_por_pagina == 50 ? 'selected' : '' ?>>50 registros</option>
                <option value="100" <?= $registros_por_pagina == 100 ? 'selected' : '' ?>>100 registros</option>
            </select>
        </div>
        
        <button class="btn-filter-fichas" onclick="filtrarPorFichas()">
            <i class="fas fa-filter"></i> Filtrar por fichas
            <?php if ($filtro_fichas): ?>
                <span class="filter-active">✓</span>
            <?php endif; ?>
        </button>

        <?php if (puedeCrear('aprendices')): ?>
        <a href="crud/crear_aprendiz.php" class="btn-create">
            <i class="fas fa-plus"></i> Nuevo Aprendiz
        </a>
        <?php endif; ?>
    </div>

    <div class="table-container">
        <table class="data-table-aprendices">
            <thead>
                <tr>
                    <th class="filter-header" data-columna="a.NOMBRES">Nombres <i class="fas fa-search"></i></th>
                    <th class="filter-header" data-columna="a.APELLIDOS">Apellidos <i class="fas fa-search"></i></th>
                    <th class="filter-header" data-columna="a.NUMERO_DOCUMENTO">Documento <i class="fas fa-search"></i></th>
                    <th class="filter-header" data-columna="a.EMAIL">Email <i class="fas fa-search"></i></th>
                    <th class="filter-header" data-columna="f.CODIGO_FICHA">Ficha <i class="fas fa-search"></i></th>
                    <th>Programa</th>
                    <th class="filter-header" data-columna="a.ESTADO_ACADEMICO">Estado <i class="fas fa-search"></i></th>
                    <th>Riesgo</th>
                    <th colspan="<?= esAdmin() ? 3 : 1 ?>">Acciones</th>
                </tr>
            </thead>
            <tbody>

            <?php if (!empty($aprendices)): ?>
                <?php foreach ($aprendices as $a): 
                    $nivelRiesgo = $aprendizDAO->calcularNivelRiesgo($a);
                    $riesgoClass = '';
                    $riesgoTexto = '';
                    switch(strtolower($nivelRiesgo)) {
                        case 'alto':
                        case 'critico':
                            $riesgoClass = 'riesgo-alto';
                            $riesgoTexto = 'ALTO RIESGO';
                            break;
                        case 'medio':
                            $riesgoClass = 'riesgo-medio';
                            $riesgoTexto = 'RIESGO MEDIO';
                            break;
                        default:
                            $riesgoClass = 'riesgo-bajo';
                            $riesgoTexto = 'SIN RIESGO';
                            break;
                    }
                ?>
                    <tr>
                        <td><?= htmlspecialchars($a['NOMBRES'] ?? '') ?></td>
                        <td><?= htmlspecialchars($a['APELLIDOS'] ?? '') ?></td>
                        <td><?= htmlspecialchars($a['NUMERO_DOCUMENTO'] ?? '') ?></td>
                        <td><?= htmlspecialchars($a['EMAIL'] ?? '') ?></td>
                        <td>
                            <?php if (!empty($a['CODIGO_FICHA'])): ?>
                                <span class="ficha-code"><?= htmlspecialchars($a['CODIGO_FICHA']) ?></span>
                            <?php else: ?>
                                <span class="sin-ficha">Sin ficha</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($a['PROGRAMA_NOMBRE'] ?? '') ?></td>
                        <td>
                            <span class="estado-badge <?= strtolower($a['ESTADO_ACADEMICO'] ?? '') ?>">
                                <?= htmlspecialchars($a['ESTADO_ACADEMICO'] ?? 'Sin estado') ?>
                            </span>
                        </td>
                        <td>
                            <span class="<?= $riesgoClass ?>">
                                <i class="fas fa-exclamation-triangle"></i> <?= $riesgoTexto ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <!-- Botón de detalle (siempre visible) -->
                            <a href="aprendiz_detalle.php?id=<?= $a['APRENDIZ_ID'] ?>" class="btn-view-all" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </a>
                            
                            <!-- Botones de editar y eliminar (solo para admin) -->
                            <?php if (esAdmin()): ?>
                                <a href="crud/editar_aprendiz.php?id=<?= $a['APRENDIZ_ID'] ?>" class="btn-edit" title="Editar aprendiz">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" onclick="confirmarEliminacion(<?= $a['APRENDIZ_ID'] ?>)" class="btn-delete" title="Eliminar aprendiz">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= esAdmin() ? 11 : 9 ?>" class="empty-state">
                        <i class="fas fa-users"></i> No hay aprendices registrados
                        <?php if (!empty($busqueda)): ?><br><small>con los filtros aplicados</small><?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>

            </tbody>
        </table>
    </div>

    <?php if ($total_registros > 0): ?>
    <div class="records-info">
        Mostrando <?= $inicio + 1 ?> - <?= min($inicio + $registros_por_pagina, $total_registros) ?> de <?= $total_registros ?> registros
    </div>
    <?php endif; ?>

    <?php if ($total_paginas > 1): ?>
    <div class="pagination-container">
        <div class="pagination">
            <a href="<?= $url_base ?>&pagina=1" class="pagination-btn <?= $pagina <= 1 ? 'disabled' : '' ?>"><i class="fas fa-angle-double-left"></i></a>
            <a href="<?= $url_base ?>&pagina=<?= max(1, $pagina - 1) ?>" class="pagination-btn <?= $pagina <= 1 ? 'disabled' : '' ?>"><i class="fas fa-angle-left"></i></a>
            
            <?php
            $rango = 2;
            $inicio_rango = max(1, $pagina - $rango);
            $fin_rango = min($total_paginas, $pagina + $rango);
            
            if ($inicio_rango > 1) {
                echo '<a href="' . $url_base . '&pagina=1" class="pagination-btn">1</a>';
                if ($inicio_rango > 2) echo '<span class="pagination-btn disabled">...</span>';
            }
            for ($i = $inicio_rango; $i <= $fin_rango; $i++):
                echo '<a href="' . $url_base . '&pagina=' . $i . '" class="pagination-btn ' . ($i == $pagina ? 'active' : '') . '">' . $i . '</a>';
            endfor;
            if ($fin_rango < $total_paginas) {
                if ($fin_rango < $total_paginas - 1) echo '<span class="pagination-btn disabled">...</span>';
                echo '<a href="' . $url_base . '&pagina=' . $total_paginas . '" class="pagination-btn">' . $total_paginas . '</a>';
            }
            ?>
            
            <a href="<?= $url_base ?>&pagina=<?= min($total_paginas, $pagina + 1) ?>" class="pagination-btn <?= $pagina >= $total_paginas ? 'disabled' : '' ?>"><i class="fas fa-angle-right"></i></a>
            <a href="<?= $url_base ?>&pagina=<?= $total_paginas ?>" class="pagination-btn <?= $pagina >= $total_paginas ? 'disabled' : '' ?>"><i class="fas fa-angle-double-right"></i></a>
        </div>
    </div>
    <?php endif; ?>

</main>

<?php include "../config/footer.php"; ?>

<div id="filter-containers"></div>

<script src="../js/tema.js"></script>
<script src="../js/loader.js"></script>
<script src="../js/dropdowns.js"></script>
<script src="../js/profile_menu.js"></script>
<script src="../js/sweetalerts.js"></script>
<script src="../js/menu.js"></script>

<script>
function cambiarRegistros(cantidad) {
    let url = new URL(window.location.href);
    url.searchParams.set('registros', cantidad);
    url.searchParams.set('pagina', '1');
    window.location.href = url.toString();
}

function filtrarPorFichas() {
    let url = new URL(window.location.href);
    let actual = url.searchParams.get('filtro_fichas');
    if (actual === '1') {
        url.searchParams.delete('filtro_fichas');
    } else {
        url.searchParams.set('filtro_fichas', '1');
    }
    url.searchParams.set('pagina', '1');
    window.location.href = url.toString();
}

function confirmarEliminacion(id) {
    Swal.fire({
        title: '¿Eliminar aprendiz?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'crud/eliminar_aprendiz.php?id=' + id;
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const filterHeaders = document.querySelectorAll('.filter-header');
    const filterContainers = document.getElementById('filter-containers');
    
    filterHeaders.forEach(header => {
        const columna = header.dataset.columna;
        let headerText = '';
        header.childNodes.forEach(node => {
            if (node.nodeType === 3) headerText += node.textContent.trim();
        });
        if (!headerText) headerText = columna.replace('a.', '').replace('f.', '');
        
        const container = document.createElement('div');
        container.className = 'filter-input-container';
        container.id = `filter-${columna.replace('.', '-')}`;
        container.innerHTML = `
            <input type="text" id="input-${columna.replace('.', '-')}" placeholder="Buscar por ${headerText.toLowerCase()}...">
            <div class="filter-actions">
                <button class="filter-apply" data-columna="${columna}">Aplicar</button>
                <button class="filter-clear" data-columna="${columna}">Limpiar</button>
            </div>
        `;
        filterContainers.appendChild(container);
    });
    
    filterHeaders.forEach(header => {
        header.addEventListener('click', function(e) {
            e.stopPropagation();
            const columna = this.dataset.columna;
            const filterBox = document.getElementById(`filter-${columna.replace('.', '-')}`);
            if (!filterBox) return;
            
            document.querySelectorAll('.filter-input-container').forEach(f => {
                if (f.id !== `filter-${columna.replace('.', '-')}`) f.classList.remove('active');
            });
            
            const rect = this.getBoundingClientRect();
            filterBox.style.position = 'fixed';
            filterBox.style.top = (rect.bottom + window.scrollY + 5) + 'px';
            filterBox.style.left = (rect.left + window.scrollX) + 'px';
            filterBox.style.width = '250px';
            filterBox.classList.toggle('active');
            if (filterBox.classList.contains('active')) {
                document.getElementById(`input-${columna.replace('.', '-')}`).focus();
            }
        });
    });
    
    document.querySelectorAll('.filter-apply').forEach(btn => {
        btn.addEventListener('click', function() {
            const columna = this.dataset.columna;
            const input = document.getElementById(`input-${columna.replace('.', '-')}`);
            const valor = input.value.trim();
            if (valor) {
                let url = new URL(window.location.href);
                url.searchParams.set('columna', columna);
                url.searchParams.set('busqueda', valor);
                url.searchParams.set('pagina', '1');
                window.location.href = url.toString();
            }
        });
    });
    
    document.querySelectorAll('.filter-clear').forEach(btn => {
        btn.addEventListener('click', function() {
            window.location.href = 'aprendices.php';
        });
    });
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.filter-header') && !e.target.closest('.filter-input-container')) {
            document.querySelectorAll('.filter-input-container').forEach(f => f.classList.remove('active'));
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.filter-input-container').forEach(f => f.classList.remove('active'));
        }
    });
    
    <?php if (!empty($columna) && !empty($busqueda)): ?>
    const inputActivo = document.getElementById('input-<?= str_replace('.', '-', $columna) ?>');
    if (inputActivo) inputActivo.value = '<?= htmlspecialchars($busqueda, ENT_QUOTES) ?>';
    <?php endif; ?>
});
</script>

</body>
</html>