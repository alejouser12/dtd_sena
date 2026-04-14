<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/AlertaDAO.php';
require_once __DIR__ . '/permissions.php'; // Para usar las funciones de permisos

// Calcular ruta base dinámica según el nivel de carpeta
$requestDir = trim(dirname($_SERVER['PHP_SELF']), '/');
$ruta_base = '';
if ($requestDir !== '') {
    $segments = explode('/', $requestDir);
    $ruta_base = str_repeat('../', count($segments));
}

$alertaDAO = new AlertaDAO();
$conteoAlertas = $alertaDAO->obtenerConteoAlertas();
$totalAlertas = $conteoAlertas['total'] ?? 0;

// Obtener datos del usuario desde la sesión
$nombreUsuario = $_SESSION['usuario_nombre'] ?? 'Invitado';
$emailUsuario = $_SESSION['usuario_email'] ?? 'usuario@sena.edu.co';
$rolUsuario = $_SESSION['usuario_rol'] ?? 'aprendiz';
$iniciales = isset($_SESSION['usuario_nombre']) ? substr($_SESSION['usuario_nombre'], 0, 2) : 'IN';
?>
<header class="header">
    <div class="header-top">
        <div class="header-top-content">
            <div class="logo-sena">
                <div class="logo-sena-container">
                    <img src="<?= $ruta_base ?>img/logo_sena_verde.png" alt="SENA" width="60" height="60" decoding="async" fetchpriority="high">
                </div>
                <span class="logo-text">DTD<span class="green">SENA</span></span>
            </div>
            <div class="ministerio">
                <img src="<?= $ruta_base ?>img/ministerio_de_trabajo.png" alt="Ministerio" width="120" height="40" decoding="async" loading="lazy">
            </div>
        </div>
    </div>
    <nav class="nav-bar">
        <div class="nav-bar-content">
            <div class="nav-left">
                <button onclick="window.location.href='<?= $ruta_base ?>index.php'" class="nav-item menu-toggle-btn">Inicio</button>

                <!-- MENÚ -->
                <div class="menu-container">
                    <button id="menu-btn" class="profile-btn">
                        <div class="profile-avatar"><i class="fas fa-bars"></i></div>
                    </button>
                    
                    <div id="menu-dropdown" class="profile-menu">
                        <div class="profile-menu-header">
                            <i class="fas fa-bars" style="font-size: 32px; color: white; margin-bottom: 10px;"></i>
                            <div class="profile-menu-name">Módulos</div>
                            <div class="profile-menu-email">Sistema DTD</div>
                        </div>
                        
                        <a href="<?= $ruta_base ?>mod/aprendices.php" class="profile-menu-item">
                            <i class="fas fa-user-graduate"></i> Aprendices
                        </a>
                        <a href="<?= $ruta_base ?>mod/instructores.php" class="profile-menu-item">
                            <i class="fas fa-chalkboard-teacher"></i> Instructores
                        </a>
                        
                        <?php if (esAdmin()): // Solo admin ve regionales, programas y fichas ?>
                        <a href="<?= $ruta_base ?>mod/regionales.php" class="profile-menu-item">
                            <i class="fas fa-map-marked-alt"></i> Regionales
                        </a>
                        <a href="<?= $ruta_base ?>mod/programas.php" class="profile-menu-item">
                            <i class="fas fa-book"></i> Programas
                        </a>
                        <a href="<?= $ruta_base ?>mod/fichas.php" class="profile-menu-item">
                            <i class="fas fa-layer-group"></i> Fichas
                        </a>
                        <?php endif; ?>
                        
                        <a href="<?= $ruta_base ?>mod/alertas.php" class="profile-menu-item">
                            <i class="fas fa-bell"></i> Alertas
                            <?php if($totalAlertas > 0): ?>
                                <span style="background:#dc2626; color:white; padding:2px 8px; border-radius:10px; font-size:11px; margin-left:auto;"><?= $totalAlertas ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?= $ruta_base ?>mod/asistencias.php" class="profile-menu-item">
                            <i class="fas fa-calendar-check"></i> Asistencia
                        </a>
                        <a href="<?= $ruta_base ?>mod/evidencias.php" class="profile-menu-item">
                            <i class="fas fa-file-alt"></i> Evidencias
                        </a>
                        
                        <div class="profile-menu-footer">
                            <span>© 2026 DTD</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="nav-right">
                <button id="theme-toggle" class="nav-item"><i id="theme-icon" class="fas fa-moon"></i></button>
                
                <!-- PERFIL -->
                <div class="profile-container">
                    <button id="profile-btn" class="profile-btn">
                        <div class="profile-avatar"><?= $iniciales ?></div>
                    </button>
                    
                    <div id="profile-backdrop" class="profile-backdrop"></div>
                    
                    <div id="profile-menu" class="profile-menu">
                        <div class="profile-menu-header">
                            <div class="profile-menu-avatar"><?= $iniciales ?></div>
                            <div class="profile-menu-name"><?= htmlspecialchars($nombreUsuario) ?></div>
                            <div class="profile-menu-email"><?= htmlspecialchars($emailUsuario) ?></div>
                            <div class="profile-menu-role"><?= ucfirst($rolUsuario) ?></div>
                        </div>
                        
                        <a href="<?= $ruta_base ?>mod/perfil.php" class="profile-menu-item"><i class="fas fa-user"></i> Perfil</a>
                        <a href="<?= $ruta_base ?>mod/ayuda.php" class="profile-menu-item"><i class="fas fa-question-circle"></i> Ayuda</a>
                        <a href="<?= $ruta_base ?>logout.php" class="profile-menu-item"><i class="fas fa-sign-out-alt"></i> Salir</a>
                        
                        <div class="profile-menu-footer">
                            <a href="#">Privacidad</a> • <a href="#">Términos</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>

<script>
    document.body.classList.add('page-loading');
</script>

<style>
.menu-container {
    position: relative;
    display: inline-block;
}
#menu-dropdown {
    position: absolute;
    top: 45px;
    left: 0;
    width: 250px;
    z-index: 1000;
}
</style>