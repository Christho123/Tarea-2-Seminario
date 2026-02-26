<?php
// CONFIGURACIÓN DE SESIONES - ANTES DE session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

session_start();
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Empresa</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="dashboard">
    <aside class="sidebar">
        <div class="logo">EMPRESA</div>
        
        <nav class="menu">
            <a href="dashboard.php" class="menu-item activo">Inicio</a>
            <a href="auditoria.php" class="menu-item">Auditoría</a>
        </nav>
        
        <form action="logout.php" method="POST">
            <button type="submit" class="btn-logout">Cerrar Sesión</button>
        </form>
    </aside>
    
    <main class="contenido">
        <div class="header-dashboard">
            <h1>BIENVENIDO A LA EMPRESA</h1>
            <div class="info-usuario">
                Usuario: <?php echo htmlspecialchars($_SESSION['email']); ?> | 
                ID: <?php echo $_SESSION['user_id']; ?>
            </div>
        </div>
        
        <div style="background:#e0e7ff;padding:20px;border-radius:10px;margin-top:20px;">
            <h3 style="color:#1e3a5f;margin-bottom:10px;">🔐 Sesión verificada con OTP</h3>
            <p style="color:#666;">Su acceso fue verificado mediante doble factor de autenticación.</p>
        </div>
    </main>
</body>
</html>