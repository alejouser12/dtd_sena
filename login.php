<?php
session_start();

// Si ya hay sesión, redirigir según rol
if (isset($_SESSION['usuario_id'])) {
    $rol = $_SESSION['usuario_rol'] ?? '';
    if ($rol === 'aprendiz') {
        header('Location: mod/aprendiz/index.php');
        exit;
    } elseif ($rol === 'instructor') {
        header('Location: mod/instructor_dashboard.php');
        exit;
    } else {
        header('Location: index.php');
        exit;
    }
}

require_once __DIR__ . '/conexion/conexion.php';

// ============================================
// CONFIGURACIÓN DE RECAPTCHA
// ============================================
define('RECAPTCHA_SITE_KEY', '6LcEURQtAAAAAKhzDNFC4HM24ukg39Un0PhxraYC');
define('RECAPTCHA_SECRET_KEY', '6LcEURQtAAAAAExyqE6aldPuxDjDwaYC33SwED5d');
// ============================================

class LoginDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    public function verificarCredenciales($email, $password)
    {
        $sql = "SELECT * FROM usuarios WHERE email = :email";
        $stmt = $this->ejecutarPreparado($sql, [':email' => $email]);
        if ($stmt) {
            $usuario = $stmt->fetch();
            if ($usuario && password_verify($password, $usuario['password'])) {
                return $usuario;
            }
        }
        return false;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $loginDAO = new LoginDAO();

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // Verificar reCAPTCHA
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    if (empty($recaptcha_response)) {
        $error = 'Debes completar el captcha (No soy un robot).';
    } else {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => RECAPTCHA_SECRET_KEY,
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        $context = stream_context_create($options);
        $verify = file_get_contents($url, false, $context);
        $captcha_success = json_decode($verify);
        if (!$captcha_success->success) {
            $error = 'Error en la verificación del captcha. Intenta de nuevo.';
        }
    }

    if (empty($error) && (empty($email) || empty($password))) {
        $error = 'Todos los campos son obligatorios.';
    }

    if (empty($error)) {
        $usuario = $loginDAO->verificarCredenciales($email, $password);
        if ($usuario) {
            session_regenerate_id(true);

            $_SESSION['usuario_id']     = $usuario['usuario_id'];
            $_SESSION['usuario_email']  = $usuario['email'];
            $_SESSION['usuario_rol']    = $usuario['rol'];
            $_SESSION['usuario_ref_id'] = $usuario['referencia_id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'] ?? $usuario['email'];

            $rol = $usuario['rol'];
            if ($rol === 'aprendiz') {
                header('Location: mod/aprendiz/index.php');
                exit;
            } elseif ($rol === 'instructor') {
                header('Location: mod/instructor_dashboard.php');
                exit;
            } else {
                header('Location: index.php');
                exit;
            }
        } else {
            $error = 'Credenciales incorrectas. Verifica tu correo y contraseña.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Iniciar Sesión — DTD SENA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        .login-page {
            background: var(--color-gris-fondo) !important;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        .login-wrapper {
            background: var(--color-blanco) !important;
            border-radius: 28px !important;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25) !important;
            position: relative;
            z-index: 20;
            width: 460px;
            max-width: 90%;
            padding: 28px 30px 32px;
        }
        .login-form#login-form {
            display: block !important;
            opacity: 1 !important;
        }
        .login-tabs {
            display: none !important;
        }
        .password-container {
            position: relative;
        }
        .password-container .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            z-index: 5;
        }
        .password-container .toggle-password:hover {
            color: #39a900;
        }
        .password-container input {
            padding-right: 40px !important;
        }
        .password-container .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            z-index: 5;
        }
        .bg-logos {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .bg-logos .float-logo {
            position: absolute;
            width: auto;
            height: auto;
            opacity: 0.2;
            animation: floatWide 25s infinite alternate ease-in-out;
            user-select: none;
            will-change: transform;
        }
        @keyframes floatWide {
            0% { transform: translate(0px, 0px) rotate(0deg) scale(1); }
            20% { transform: translate(80px, -40px) rotate(8deg) scale(1.05); }
            40% { transform: translate(-60px, 70px) rotate(-6deg) scale(0.95); }
            60% { transform: translate(100px, 30px) rotate(10deg) scale(1.02); }
            80% { transform: translate(-40px, -70px) rotate(-8deg) scale(0.98); }
            100% { transform: translate(30px, 50px) rotate(5deg) scale(1); }
        }
        @media (max-width: 768px) {
            .bg-logos .float-logo { display: none; }
        }
        .dark-mode .bg-logos .float-logo { opacity: 0.1; }
        .recaptcha-container {
            margin: 20px 0;
            display: flex;
            justify-content: center;
        }
    </style>
</head>
<body class="login-page">

<div class="bg-logos">
    <?php
    for ($i = 0; $i < 50; $i++) {
        $size = rand(40, 120);
        $top = rand(0, 95);
        $left = rand(0, 95);
        $duration = rand(18, 35);
        $delay = rand(-15, 20);
        $opacity = rand(10, 25) / 100;
        echo "<img src='img/logo_sena_verde.png' class='float-logo' style='width: {$size}px; top: {$top}%; left: {$left}%; animation-duration: {$duration}s; animation-delay: {$delay}s; opacity: {$opacity};' alt='Logo SENA'>\n";
    }
    ?>
</div>

<div class="login-wrapper">
    <div class="login-shell">
        <div class="login-header">
            <div class="login-logo">
                <img src="img/logo_sena_verde.png" alt="Logo SENA">
                <span class="login-brand">DTD_<span class="green">SENA</span></span>
            </div>
            <a href="https://portal.senasofiaplus.edu.co/">
                <i class="fa-solid fa-arrow-left"></i> Ir a Portal SENA
            </a>
        </div>

        <?php if ($error): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de acceso',
                    text: '<?= addslashes($error) ?>',
                    confirmButtonColor: '#39a900'
                });
            });
        </script>
        <?php endif; ?>

        <form method="POST" action="" class="login-form active" id="login-form">
            <div class="form-field">
                <label for="login-email">Correo electrónico *</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email"
                           name="email"
                           id="login-email"
                           placeholder="usuario@example.com"
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                           autocomplete="email"
                           required />
                </div>
            </div>

            <div class="form-field">
                <label for="login-password">Contraseña *</label>
                <div class="password-container">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password"
                           name="password"
                           id="login-password"
                           placeholder="Contraseña"
                           autocomplete="current-password"
                           required />
                    <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
                </div>
            </div>

            <div class="recaptcha-container">
                <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
            </div>

            <button type="submit" name="login" class="btn-login">
                <i class="fa-solid fa-cloud"></i> Iniciar sesión
            </button>

            <div class="help-links">
                ¿No recuerdas tu contraseña? <a href="#">Restablecer</a><br>
                ¿Tu cuenta está inactiva? <a href="#">Más información</a>
            </div>
        </form>

        <div class="footer-login">
            © 2026 DTD_SENA. Todos los derechos reservados.
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('login-password');
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
            });
        }
    });
</script>
<script src="js/tema.js"></script>
</body>
</html>