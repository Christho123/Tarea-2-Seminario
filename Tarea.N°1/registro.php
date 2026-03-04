<?php
// CONFIGURACIÓN DE SESIONES
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

session_start();
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$exito = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmar = $_POST['confirmar_password'];
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Correo electrónico no válido";
    } elseif(strlen($password) < 4) {
        $error = "La contraseña debe tener al menos 4 caracteres";
    } elseif($password !== $confirmar) {
        $error = "Las contraseñas no coinciden";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if($stmt->fetch()) {
            $error = "El correo ya está registrado";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
            
            if($stmt->execute([$email, $hash])) {
                $user_id = $pdo->lastInsertId();
                registrarAuditoria($pdo, $user_id, 'USER_REGISTRATION', "Email: $email");
                $exito = "Registro exitoso. Ahora puede iniciar sesión.";
            } else {
                $error = "Error al registrar usuario";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - Analytics Pro</title>
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
            --success: #2ecc71;
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
            max-width: 450px;
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
        .subtitle { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem; }

        /* Mensajes */
        .mensaje {
            padding: 0.8rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.2rem;
            font-weight: 500;
            border: 1px solid transparent;
        }
        .mensaje-error { 
            background: rgba(246, 78, 96, 0.1); 
            color: var(--danger); 
            border-color: rgba(246, 78, 96, 0.2); 
        }
        .mensaje-exito { 
            background: rgba(46, 204, 113, 0.1); 
            color: var(--success); 
            border-color: rgba(46, 204, 113, 0.2); 
        }

        /* Formulario */
        .grupo-input { text-align: left; margin-bottom: 1rem; }
        .grupo-input label { 
            display: block; 
            font-size: 0.8rem; 
            font-weight: 700; 
            margin-bottom: 0.4rem; 
            color: #4a5568;
            text-transform: uppercase;
        }

        input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.95rem;
            background: #f8fafc;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        /* Password Wrapper */
        .input-password { position: relative; }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--primary);
            font-size: 0.7rem;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
        }

        .btn-primario {
            width: 100%;
            padding: 0.9rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-primario:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
        }

        .btn-secundario {
            display: block;
            margin-top: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .footer-login {
            margin-top: 2rem;
            padding-top: 1.2rem;
            border-top: 1px solid #f1f5f9;
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

    <div class="contenedor-login">
        <div class="logo-login">Crea tu cuenta</div>

        <?php if($error): ?>
            <div class="mensaje mensaje-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($exito): ?>
            <div class="mensaje mensaje-exito"><?php echo $exito; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="formRegistro">
            <div class="grupo-input">
                <label>Email Corporativo</label>
                <input type="email" name="email" required placeholder="usuario@empresa.com">
            </div>
            
            <div class="grupo-input">
                <label>Contraseña</label>
                <div class="input-password">
                    <input type="password" name="password" id="password" required placeholder="Mínimo 4 caracteres">
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)">Ver</button>
                </div>
            </div>
            
            <div class="grupo-input">
                <label>Confirmar Contraseña</label>
                <div class="input-password">
                    <input type="password" name="confirmar_password" id="confirmar" required placeholder="Repita su contraseña">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirmar', this)">Ver</button>
                </div>
            </div>
            
            <button type="submit" class="btn-primario">Registrarse</button>
        </form>
        
        <a href="index.php" class="btn-secundario">¿Ya tienes cuenta? Inicia Sesión</a>
        
        <div class="footer-login">
            Protección de Datos Nivel Empresarial
        </div>
    </div>

    <script>
        function togglePassword(id, btn) {
            const input = document.getElementById(id);
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