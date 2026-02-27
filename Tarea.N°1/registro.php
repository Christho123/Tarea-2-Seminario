<?php
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
    
    // Validaciones básicas
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Correo electrónico no válido";
    } elseif(strlen($password) < 4) {
        $error = "La contraseña debe tener al menos 4 caracteres";
    } elseif($password !== $confirmar) {
        $error = "Las contraseñas no coinciden";
    } else {
        // Verificar si email existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if($stmt->fetch()) {
            $error = "El correo ya está registrado";
        } else {
            // Crear usuario con hash (según manual página 1)
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
            
            if($stmt->execute([$email, $hash])) {
                $user_id = $pdo->lastInsertId();
                
                // Auditoría de registro (según manual)
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
    <title>Crear Cuenta</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="registro-page">
    <div class="contenedor-login">
        <h2>Crear Cuenta</h2>
        
        <?php if($error): ?>
            <div class="mensaje-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($exito): ?>
            <div class="mensaje-exito"><?php echo $exito; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="formRegistro">
            <div class="grupo-input">
                <label>Correo Empresarial</label>
                <input type="email" name="email" required placeholder="usuario@empresa.com">
            </div>
            
            <div class="grupo-input">
                <label>Contraseña</label>
                <div class="input-password">
                    <input type="password" name="password" id="password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)">Mostrar</button>
                </div>
            </div>
            
            <div class="grupo-input">
                <label>Confirmar Contraseña</label>
                <div class="input-password">
                    <input type="password" name="confirmar_password" id="confirmar" required placeholder="Repita la contraseña">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirmar', this)">Mostrar</button>
                </div>
            </div>
            
            <button type="submit" class="btn-primario">Registrarse</button>
        </form>
        
        <a href="index.php" class="btn-secundario">Volver al Login</a>
        
        <div class="footer-login">
            Sistema de Seguridad Empresarial
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
                btn.textContent = 'Mostrar';
            }
        }
    </script>
</body>
</html>