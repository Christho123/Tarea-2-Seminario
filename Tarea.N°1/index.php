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
    
    $intentos = verificarIntentosFallidos($pdo, $email);
    if($intentos >= 5) {
        $error = "Cuenta bloqueada temporalmente. Intente en 15 minutos.";
        registrarAuditoria($pdo, null, 'LOGIN_BLOCKED', "Email: $email");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($usuario && password_verify($password, $usuario['password_hash'])) {
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
    <title>Acceso al Sistema - Analytics Pro</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap">
    <style>
        :root {
            --primary: #4361ee;
            --primary-hover: #3751d4;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --white: #ffffff;
            --text-main: #1e1e2d;
            --text-muted: #b5b5c3;
            --danger: #f64e60;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
        }

        .contenedor-login {
            background: var(--white);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            text-align: center;
        }

        .logo-login {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
        }

        h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: 0.5rem; color: #1e1e2d; }
        .subtitle { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem; }

        /* Mensajes de Error */
        .mensaje-error {
            background: rgba(246, 78, 96, 0.1);
            color: var(--danger);
            padding: 0.8rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(246, 78, 96, 0.2);
            font-weight: 500;
        }

        /* Formulario */
        .grupo-input { text-align: left; margin-bottom: 1.2rem; }
        .grupo-input label { 
            display: block; 
            font-size: 0.85rem; 
            font-weight: 600; 
            margin-bottom: 0.5rem; 
            color: #4a5568;
        }

        input {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
        }

        /* Password Toggle */
        .input-password { position: relative; }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
        }

        .btn-primario {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-primario:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-secundario {
            display: block;
            margin-top: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .footer-login {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #f1f5f9;
            font-size: 0.75rem;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body class="login-page">

    <div class="contenedor-login">
        <div class="logo-login">Bienvenido</div>
        <h2>Sistema CRM</h2>
        
        <?php if($error): ?>
            <div class="mensaje-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="grupo-input">
                <label>Correo Corporativo</label>
                <input type="email" name="email" required placeholder="nombre@empresa.com">
            </div>
            
            <div class="grupo-input">
                <label>Contraseña</label>
                <div class="input-password">
                    <input type="password" name="password" id="password" required placeholder="••••••••">
                    <button type="button" class="toggle-password" onclick="togglePassword()">Ver</button>
                </div>
            </div>
            
            <button type="submit" class="btn-primario">Iniciar Sesión</button>
        </form>
        
        <a href="registro.php" class="btn-secundario">¿No tienes cuenta? Regístrate</a>
        
        <div class="footer-login">
            SECURITY PROTOCOL v3.0 | 2FA ENABLED
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
                btn.textContent = 'Ver';
            }
        }
    </script>
</body>
</html>