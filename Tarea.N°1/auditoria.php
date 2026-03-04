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

// Registrar la visita a este módulo
try {
    registrarVisitaWeb($pdo, 'Auditoría', $_SERVER['REQUEST_URI'] ?? '', $_SESSION['user_id']);
} catch (PDOException $e) {}

// Obtener logs de auditoría
$stmt = $pdo->query("SELECT a.*, u.email 
                     FROM audit_logs a 
                     LEFT JOIN users u ON a.user_id = u.id 
                     ORDER BY a.created_at DESC 
                     LIMIT 100");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría - Sistema Pro</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap">
    <style>
        :root {
            --primary: #4361ee;
            --success: #2ecc71;
            --danger: #f64e60;
            --warning: #ff9f43;
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
            color: var(--danger);
            border: none;
            padding: 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: auto;
        }
        .btn-logout:hover { background: var(--danger); color: white; }

        /* --- CONTENIDO --- */
        .contenido {
            margin-left: var(--sidebar-width);
            flex-grow: 1;
            padding: 2.5rem;
            max-width: 1400px;
        }

        .header-dashboard { margin-bottom: 2rem; }
        .header-dashboard h1 { font-size: 1.8rem; font-weight: 700; }
        .header-dashboard p { color: var(--text-muted); }

        /* Tabla de Auditoría */
        .contenedor-blanco {
            background: var(--white);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid #edf2f7;
        }

        .tabla-datos {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-datos th {
            text-align: left;
            padding: 1rem;
            background: #fcfcfd;
            color: var(--text-muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            border-bottom: 1px solid #edf2f7;
        }

        .tabla-datos td {
            padding: 1rem;
            border-bottom: 1px solid #f8f9fc;
            font-size: 0.85rem;
        }

        .tabla-datos tr:hover { background-color: #fcfcfd; }

        /* Badges de Acción */
        .badge-accion {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-block;
        }

        .estado-exito { background: #e8fff3; color: var(--success); }
        .estado-fallo { background: #fff5f5; color: var(--danger); }
        .estado-info { background: #e1f0ff; color: var(--primary); }

        .ip-text { font-family: monospace; color: #666; background: #eee; padding: 2px 4px; border-radius: 4px; }
        .fecha-col { white-space: nowrap; color: var(--text-muted); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo">
            <span style="color: var(--primary); margin-right: 5px;">●</span> Auditoria
        </div>
        <nav class="menu">
            <a href="dashboard.php" class="menu-item">Dashboard</a>
            <a href="auditoria.php" class="menu-item activo">Auditoría</a>
            <a href="rrhh.php" class="menu-item">RRHH</a>
            <a href="analitica.php" class="menu-item">Analisis</a>
        </nav>
        <form action="logout.php" method="POST">
            <button type="submit" class="btn-logout">Cerrar Sesión</button>
        </form>
    </aside>

    <main class="contenido">
        <div class="header-dashboard">
            <h1>Registro de Auditoría</h1>
            <p>Monitoreo de seguridad y acciones realizadas en el sistema.</p>
        </div>

        <div class="contenedor-blanco">
            <table class="tabla-datos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Dirección IP</th>
                        <th>Detalles del Evento</th>
                        <th>Fecha y Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $log): 
                        // Determinar clase de color según la acción
                        $claseEstado = 'estado-info';
                        if (strpos($log['action'], 'FAILED') !== false || strpos($log['action'], 'ELIMINAR') !== false) {
                            $claseEstado = 'estado-fallo';
                        } elseif (strpos($log['action'], 'SUCCESS') !== false || strpos($log['action'], 'REGISTRADO') !== false) {
                            $claseEstado = 'estado-exito';
                        }
                    ?>
                    <tr>
                        <td style="color: var(--text-muted);">#<?php echo $log['id']; ?></td>
                        <td style="font-weight: 600;">
                            <?php echo htmlspecialchars($log['email'] ?? 'Sistema/Auto'); ?>
                        </td>
                        <td>
                            <span class="badge-accion <?php echo $claseEstado; ?>">
                                <?php echo htmlspecialchars($log['action']); ?>
                            </span>
                        </td>
                        <td><span class="ip-text"><?php echo htmlspecialchars($log['ip_address']); ?></span></td>
                        <td style="max-width: 300px;"><?php echo htmlspecialchars($log['details']); ?></td>
                        <td class="fecha-col">
                            <strong><?php echo date('d/m/Y', strtotime($log['created_at'])); ?></strong><br>
                            <small><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>