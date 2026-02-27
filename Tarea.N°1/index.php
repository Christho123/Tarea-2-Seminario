<?php
// CONFIGURACIÓN DE SESIONES - ANTES DE session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS

session_start();
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';
require_once 'includes/enviar_otp.php';

if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Verificar intentos fallidos
    $intentos = verificarIntentosFallidos($pdo, $email);
    if($intentos >= 5) {
        $error = "Cuenta temporalmente bloqueada por múltiples intentos. Intente en 15 minutos.";
        registrarAuditoria($pdo, null, 'LOGIN_BLOCKED', "Email: $email - Intentos: $intentos");
    } else {
        // Buscar usuario
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($usuario && password_verify($password, $usuario['password_hash'])) {
            // Éxito - Generar y enviar OTP
            $resultado = generarYEnviarOTP($pdo, $usuario['id'], $email);
            
            if($resultado['status'] == 'success') {
                $_SESSION['temp_user_id'] = $usuario['id'];
                $_SESSION['temp_email'] = $email;
                
                registrarAuditoria($pdo, $usuario['id'], 'LOGIN_ATTEMPT_SUCCESS', "Email: $email");
                
                header("Location: verificar_otp.php");
                exit();
            } else {
                $error = "Error al enviar el código: " . $resultado['message'];
            }
        } else {
            $error = "Credenciales incorrectas";
            registrarAuditoria($pdo, $usuario ? $usuario['id'] : null, 'LOGIN_ATTEMPT_FAILED', "Email: $email");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - Fase 1</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="login-page">
    <div class="contenedor-login">
        <h2>Acceso al Sistema</h2>
        <p style="color:#666;text-align:center;margin-bottom:20px;font-size:14px;">Fase 1: Ingrese sus credenciales</p>
        
        <?php if($error): ?>
            <div class="mensaje-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="grupo-input">
                <label>Correo Empresarial</label>
                <input type="email" name="email" required placeholder="ejemplo@empresa.com">
            </div>
            
            <div class="grupo-input">
                <label>Contraseña</label>
                <div class="input-password">
                    <input type="password" name="password" id="password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">Mostrar</button>
                </div>
            </div>
            
            <button type="submit" class="btn-primario">Continuar</button>
        </form>
        
        <a href="registro.php" class="btn-secundario">Registrarse</a>
        
        <div class="footer-login">
            Sistema de Seguridad Empresarial
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const btn = document.querySelector('.toggle-password');
            if(input.type === 'password') {
                input.type = 'text';
                btn.textContent = 'Ocultar';
            } else {
                input.type = 'password';
                btn.textContent = 'Mostrar';
            }
        }
    </script>
</body>
</html>