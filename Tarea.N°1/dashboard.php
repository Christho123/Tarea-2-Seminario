<?php
// CONFIGURACIÓN DE SESIONES
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

session_start();
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
try {
    registrarVisitaWeb($pdo, 'Dashboard', $_SERVER['REQUEST_URI'] ?? '', $_SESSION['user_id']);
} catch (PDOException $e) {}

$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Consultas rápidas para los indicadores
$total_empleados = $pdo->query("SELECT COUNT(*) FROM empleados")->fetchColumn();
$total_logs = $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema CRM</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap">
    <style>
        :root {
            --primary: #4361ee;
            --success: #2ecc71;
            --bg-body: #f8f9fc;
            --sidebar-bg: #1e1e2d;
            --text-main: #3f4254;
            --text-muted: #b5b5c3;
            --white: #ffffff;
            --sidebar-width: 260px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* --- SIDEBAR FIJA --- */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            z-index: 100;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 2.5rem;
            display: flex;
            align-items: center;
        }

        .menu { display: flex; flex-direction: column; gap: 0.5rem; flex-grow: 1; }

        .menu-item {
            padding: 0.8rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .menu-item:hover, .menu-item.activo {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--white);
        }

        .menu-item.activo { background-color: var(--primary); }

        .btn-logout {
            background: rgba(246, 78, 96, 0.1);
            color: #f64e60;
            border: none;
            padding: 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: auto;
        }

        /* --- CONTENIDO --- */
        .contenido {
            margin-left: var(--sidebar-width);
            flex-grow: 1;
            padding: 2.5rem;
            max-width: 1400px;
        }

        .header-dashboard { margin-bottom: 2.5rem; }
        .header-dashboard h1 { font-size: 2rem; font-weight: 800; color: #1e1e2d; }
        .info-usuario { color: var(--text-muted); margin-top: 5px; font-size: 0.9rem; }

        /* Tarjetas de Resumen */
        .grid-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: var(--white);
            padding: 2rem;
            border-radius: 15px;
            border: 1px solid #edf2f7;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            position: relative;
            overflow: hidden;
        }

        .stat-card h3 { font-size: 0.85rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; }
        .stat-card .valor { font-size: 2.2rem; font-weight: 800; margin-top: 10px; color: #1e1e2d; }
        
        .stat-card::after {
            content: '';
            position: absolute;
            right: -20px;
            bottom: -20px;
            width: 100px;
            height: 100px;
            background: var(--primary);
            opacity: 0.05;
            border-radius: 50%;
        }

        /* Banner de Seguridad */
        .banner-otp {
            background: linear-gradient(135deg, #e0e7ff 0%, #f0f4ff 100%);
            padding: 2rem;
            border-radius: 15px;
            border-left: 5px solid var(--primary);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .otp-icon {
            font-size: 2.5rem;
            background: white;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.15);
        }

        .otp-text h3 { color: #1e3a5f; margin-bottom: 5px; }
        .otp-text p { color: #5e7ba2; font-size: 0.95rem; }

        .btn-accion-rapida {
            display: inline-block;
            margin-top: 2rem;
            padding: 1rem 2rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-accion-rapida:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3); }

    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo">
            <span style="color: var(--primary); margin-right: 5px;">●</span> Dashboard
        </div>
        <nav class="menu">
            <a href="dashboard.php" class="menu-item activo">Dashboard</a>
            <a href="auditoria.php" class="menu-item">Auditoría</a>
            <a href="rrhh.php" class="menu-item">RRHH</a>
            <a href="analitica.php" class="menu-item">Analisis</a>
        </nav>
        <form action="logout.php" method="POST">
            <button type="submit" class="btn-logout">Cerrar Sesión</button>
        </form>
    </aside>

    <main class="contenido">
        <div class="header-dashboard">
            <h1>Bienvenido de nuevo, <?php echo explode('@', $_SESSION['email'])[0]; ?></h1>
            <div class="info-usuario">
                Sesión iniciada como <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong> (ID: <?php echo $_SESSION['user_id']; ?>)
            </div>
        </div>

        <div class="grid-stats">
            <div class="stat-card">
                <h3>Empleados Registrados</h3>
                <div class="valor"><?php echo $total_empleados; ?></div>
            </div>
            <div class="stat-card">
                <h3>Eventos de Auditoría</h3>
                <div class="valor"><?php echo $total_logs; ?></div>
            </div>
            <div class="stat-card">
                <h3>Estado del Servidor</h3>
                <div class="valor" style="color: var(--success);">Óptimo</div>
            </div>
        </div>

        <div class="banner-otp">
            <div class="otp-icon">🔐</div>
            <div class="otp-text">
                <h3>Seguridad Nivel 2 Activa</h3>
                <p>Su cuenta está protegida. El acceso fue verificado mediante un código <strong>OTP dinámico</strong> vinculado a su dispositivo.</p>
            </div>
        </div>

        <div style="margin-top: 30px;">
            <a href="analitica.php" class="btn-accion-rapida">Ver Reporte de Tráfico Real</a>
        </div>
    </main>

</body>
</html>