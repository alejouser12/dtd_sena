<?php
session_start();

// Si ya hay sesión, redirigir al index
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/conexion/conexion.php';

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

    if (empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } else {
        $usuario = $loginDAO->verificarCredenciales($email, $password);

        if ($usuario) {
            // Limpiar sesión anterior y regenerar ID
            session_regenerate_id(true);

            $_SESSION['usuario_id']     = $usuario['usuario_id'];
            $_SESSION['usuario_email']  = $usuario['email'];
            $_SESSION['usuario_rol']    = $usuario['rol'];
            $_SESSION['usuario_ref_id'] = $usuario['referencia_id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'] ?? $usuario['email'];

            // ── Redirigir según rol ──────────────────────────────
            $rol = $usuario['rol'];
            if ($rol === 'aprendiz') {
                header('Location: mod/aprendiz_home.php');
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
    <title>Iniciar Sesión / Registro</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
/* FUERZA BRUTA - SOBRESCRIBE TODO */
.login-wrapper {
    max-width: 400px !important;
    width: 90% !important;
    margin: 20px auto !important;
    padding: 25px 28px 28px !important;
    border-radius: 24px !important;
}

@media (max-width: 600px) {
    .login-wrapper {
        max-width: 340px !important;
        padding: 20px 22px 24px !important;
    }
}
</style>
</head>
<body class="login-page">

<div class="login-backdrop" aria-hidden="true"></div>

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

    <div class="login-tabs">
        <div class="login-tab active" data-tab="login">
            <i class="fa-solid fa-right-to-bracket"></i> Iniciar sesión
        </div>
        <div class="login-tab" data-tab="signup">
            <i class="fa-solid fa-user-plus"></i> Registrarse
        </div>
    </div>

    <!-- FORM LOGIN -->
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
                <i class="fa-solid fa-eye toggle-password"></i>
            </div>
        </div>

        <button type="submit" name="login" class="btn-login">
            <i class="fa-solid fa-cloud"></i> Iniciar sesión
        </button>

        <div class="help-links">
            ¿No recuerdas tu contraseña? <a href="#">Restablecer</a><br>
            ¿Tu cuenta está inactiva? <a href="#">Más información</a>
        </div>
    </form>

    <!-- FORM REGISTRO -->
    <form method="POST" action="" class="login-form" id="signup-form">
        <div class="signup-scroll-content">
            <div class="form-field">
                <label for="signup-firstname">Nombre *</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-user input-icon"></i>
                    <input type="text" name="firstName" id="signup-firstname"
                           placeholder="Tu nombre" autocomplete="given-name" required />
                </div>
            </div>
            <div class="form-field">
                <label for="signup-lastname">Apellido *</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-user input-icon"></i>
                    <input type="text" name="lastName" id="signup-lastname"
                           placeholder="Tu apellido" autocomplete="family-name" required />
                </div>
            </div>
            <div class="form-field">
                <label for="signup-email">Correo electrónico *</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" name="email" id="signup-email"
                           placeholder="tu@email.com" autocomplete="email" required />
                </div>
            </div>
            <div class="form-field">
                <label for="signup-username">Usuario *</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-at input-icon"></i>
                    <input type="text" name="username" id="signup-username"
                           placeholder="Nombre de usuario" autocomplete="username" required />
                </div>
            </div>
            <div class="form-field">
                <label for="signup-password">Contraseña *</label>
                <div class="password-container">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" name="signupPassword" id="signup-password"
                           placeholder="Crea una contraseña" autocomplete="new-password" required />
                    <i class="fa-solid fa-eye toggle-password"></i>
                </div>
            </div>
        </div>
        <button type="submit" name="signup" class="btn-login">
            <i class="fa-solid fa-user-plus"></i> Registrarse
        </button>
    </form>

    <div class="footer-login">
        © 2026 DTD_SENA. Todos los derechos reservados.
    </div>
</div>
</div>

<script src="js/login.js"></script>
<script src="js/tema.js"></script>
</body>
</html>