<?php
// index.php
session_start();
require_once 'config/auth.php'; // <-- NUEVO: protege la página
require_once 'config/app.php';
require_once 'conexion/AlertaDAO.php';

$alertaDAO = new AlertaDAO();
$conteoAlertas = $alertaDAO->obtenerConteoAlertas();
$totalAlertas = $conteoAlertas['total'] ?? 0;
$alertasAltas = $conteoAlertas['altas'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detección de Deserción SENA</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .card {
            position: relative;
            overflow: visible !important;
        }
        
        .alerta-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            font-size: 12px;
            font-weight: 700;
            min-width: 20px;
            height: 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 10;
            border: 2px solid white;
        }
        
        .dark-mode .alerta-badge {
            border-color: #2d2d2d;
        }
        
        .alerta-tooltip {
            position: absolute;
            bottom: 10px;
            left: 10px;
            font-size: 11px;
            background: rgba(0,0,0,0.05);
            padding: 2px 8px;
            border-radius: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div id="loader">
        <img src="<?= htmlspecialchars(asset_url('img/logo_sena_verde.png')) ?>" alt="Logo SENA" id="loader-logo">
    </div>
    
    <?php include __DIR__ . '/config/header.php'; ?>
    
    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div class="page-header">
            <h1 class="page-title">Sistema de Detección de Deserción</h1>
            <p class="page-subtitle">Bienvenido. Seleccione una opción para comenzar.</p>
        </div>
        
        <ul class="cards-grid">
            <li class="card">
                <a href="<?= htmlspecialchars(app_url('mod/aprendices.php')) ?>">
                    <div class="card-icon"><i class="fas fa-user-graduate"></i></div>
                    <h3 class="card-title">Aprendices</h3>
                    <p class="card-description">Gestión de estudiantes</p>
                </a>
            </li>
            <li class="card">
                <a href="<?= htmlspecialchars(app_url('mod/instructores.php')) ?>">
                    <div class="card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <h3 class="card-title">Instructores</h3>
                    <p class="card-description">Lista de instructores</p>
                </a>
            </li>
            <li class="card">
                <a href="<?= htmlspecialchars(app_url('mod/regionales.php')) ?>">
                    <div class="card-icon"><i class="fas fa-map-marked-alt"></i></div>
                    <h3 class="card-title">Regionales</h3>
                    <p class="card-description">Gestión de regionales y centros</p>
                </a>
            </li>
            <li class="card" id="alertas-card">
                <a href="<?= htmlspecialchars(app_url('mod/alertas.php')) ?>">
                    <div class="card-icon"><i class="fas fa-bell"></i></div>
                    <h3 class="card-title">Alertas</h3>
                    <p class="card-description">Alertas y riesgos</p>
                    <?php if($totalAlertas > 0): ?>
                        <div class="alerta-badge"><?= $totalAlertas ?></div>
                    <?php endif; ?>
                    <?php if($alertasAltas > 0): ?>
                        <div class="alerta-tooltip">🔴 <?= $alertasAltas ?> alta(s)</div>
                    <?php endif; ?>
                </a>
            </li>
            <li class="card">
                <a href="<?= htmlspecialchars(app_url('mod/asistencias.php')) ?>">
                    <div class="card-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3 class="card-title">Toma de Asistencia</h3>
                    <p class="card-description">Registro de asistencia diaria</p>
                </a>
            </li>
            <li class="card">
                <a href="<?= htmlspecialchars(app_url('mod/evidencias.php')) ?>">
                    <div class="card-icon"><i class="fas fa-file-alt"></i></div>
                    <h3 class="card-title">Evidencias</h3>
                    <p class="card-description">Gestión de evidencias</p>
                </a>
            </li>
        </ul>
    </main>
    
    <!-- FOOTER -->
    <?php include 'config/footer.php'; ?>
    
    <script src="<?= htmlspecialchars(asset_url('js/tema.js')) ?>"></script>
    <script src="<?= htmlspecialchars(asset_url('js/loader.js')) ?>"></script>
    <script src="<?= htmlspecialchars(asset_url('js/panel_menu.js')) ?>"></script>
    <script src="<?= htmlspecialchars(asset_url('js/dropdowns.js')) ?>"></script>
    <script src="<?= htmlspecialchars(asset_url('js/profile_menu.js')) ?>"></script>
    <script src="<?= htmlspecialchars(asset_url('js/sweetalerts.js')) ?>"></script>
    <script src="<?= htmlspecialchars(asset_url('js/menu.js')) ?>"></script>
</body>
</html>
