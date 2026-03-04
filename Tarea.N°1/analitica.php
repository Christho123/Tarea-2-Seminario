<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

try {
    registrarVisitaWeb($pdo, 'Analítica Web', $_SERVER['REQUEST_URI'] ?? '', $_SESSION['user_id']);
} catch (PDOException $e) {}

$total_visitas = 0;
$visitas_hoy = 0;
$ultimas_visitas = [];

try {
    $total_visitas = $pdo->query("SELECT COUNT(*) FROM visitas_web")->fetchColumn();
    $visitas_hoy = $pdo->query("SELECT COUNT(*) FROM visitas_web WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $stmt = $pdo->query("SELECT v.*, u.email FROM visitas_web v LEFT JOIN users u ON v.user_id = u.id ORDER BY v.created_at DESC LIMIT 50");
    $ultimas_visitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Fechas dinámicas (Última semana)
$fechas_grafico = [];
for ($i = 6; $i >= 0; $i--) {
    $fechas_grafico[] = date('d/m', strtotime("-$i days"));
}
$datos_vistas = [16, 5, 7, 10, 13, 2, (int)$visitas_hoy]; 
$datos_sesiones = [6, 2, 6, 5, 2, 2, (int)($visitas_hoy * 0.4)]; 

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analítica - Pro</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            position: fixed; /* Se mantiene fija */
            left: 0;
            top: 0;
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
            letter-spacing: 1px;
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
            transition: 0.3s;
        }
        .btn-logout:hover { background: #f64e60; color: white; }

        /* --- CONTENIDO (CON SCROLL) --- */
        .contenido {
            margin-left: var(--sidebar-width); /* Espacio para la sidebar fija */
            flex-grow: 1;
            padding: 2.5rem;
            max-width: 1400px;
        }

        .header-dashboard { margin-bottom: 2rem; }
        .header-dashboard h1 { font-size: 1.8rem; font-weight: 700; margin-bottom: 0.5rem; }
        .header-dashboard p { color: var(--text-muted); }

        /* Tarjetas Modernas */
        .tarjetas-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .tarjeta {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid #edf2f7;
        }

        .tarjeta h3 { font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .tarjeta .valor { font-size: 2rem; font-weight: 700; margin-top: 0.5rem; color: var(--primary); }

        /* Contenedor Gráfico */
        .contenedor-grafico {
            background: var(--white);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            margin-bottom: 2rem;
            border: 1px solid #edf2f7;
        }

        /* Tabla Estilizada */
        .contenedor-tabla {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid #edf2f7;
        }

        .tabla-datos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .tabla-datos th {
            text-align: left;
            padding: 1rem;
            background: #fcfcfd;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.85rem;
            border-bottom: 1px solid #edf2f7;
        }

        .tabla-datos td { padding: 1rem; border-bottom: 1px solid #f8f9fc; font-size: 0.9rem; }
        .tabla-datos tr:hover { background-color: #fcfcfd; }

        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #e1f0ff;
            color: var(--primary);
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo">
            <span style="color: var(--primary); margin-right: 5px;">●</span> Analisis
        </div>
        <nav class="menu">
            <a href="dashboard.php" class="menu-item">Dashboard</a>
            <a href="auditoria.php" class="menu-item">Auditoría</a>
            <a href="rrhh.php" class="menu-item">RRHH</a>
            <a href="analitica.php" class="menu-item activo">Analisis</a>
        </nav>
        <form action="logout.php" method="POST">
            <button type="submit" class="btn-logout">Cerrar Sesión</button>
        </form>
    </aside>

    <main class="contenido">
        <div class="header-dashboard">
            <h1>Panel de Control</h1>
            <p>Bienvenido de nuevo. Aquí está lo que sucede en tu sitio hoy.</p>
        </div>

        <div class="tarjetas-dashboard">
            <div class="tarjeta">
                <h3>Total Visitas</h3>
                <div class="valor"><?php echo number_format($total_visitas); ?></div>
            </div>
            <div class="tarjeta">
                <h3>Visitas Hoy</h3>
                <div class="valor"><?php echo (int)$visitas_hoy; ?></div>
            </div>
            <div class="tarjeta">
                <h3>Crecimiento</h3>
                <div class="valor" style="color: var(--success);">+12.5%</div>
            </div>
        </div>

        <div class="contenedor-grafico">
            <h2 style="font-size: 1.1rem; margin-bottom: 1.5rem;">Tráfico de la última semana</h2>
            <div style="height: 350px;">
                <canvas id="graficoTrafico"></canvas>
            </div>
        </div>

        <div class="contenedor-tabla">
            <h2 style="font-size: 1.1rem; margin-bottom: 1rem;">Logs en tiempo real</h2>
            <table class="tabla-datos">
                <thead>
                    <tr>
                        <th>USUARIO</th>
                        <th>PÁGINA</th>
                        <th>IP</th>
                        <th>FECHA</th>
                        <th>ESTADO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimas_visitas as $v): ?>
                    <tr>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($v['email'] ?? 'Invitado'); ?></td>
                        <td><span class="badge"><?php echo htmlspecialchars($v['pagina']); ?></span></td>
                        <td style="color: var(--text-muted);"><?php echo htmlspecialchars($v['ip_address'] ?? '-'); ?></td>
                        <td><?php echo date('H:i', strtotime($v['created_at'])); ?> <small style="color:#b5b5c3"><?php echo date('d/m', strtotime($v['created_at'])); ?></small></td>
                        <td><span style="color: var(--success);">● Activo</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('graficoTrafico').getContext('2d');
        
        const gradientAzul = ctx.createLinearGradient(0, 0, 0, 400);
        gradientAzul.addColorStop(0, 'rgba(67, 97, 238, 0.25)');
        gradientAzul.addColorStop(1, 'rgba(67, 97, 238, 0)');

        const gradientVerde = ctx.createLinearGradient(0, 0, 0, 400);
        gradientVerde.addColorStop(0, 'rgba(46, 204, 113, 0.2)');
        gradientVerde.addColorStop(1, 'rgba(46, 204, 113, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($fechas_grafico); ?>,
                datasets: [
                    {
                        label: 'Vistas',
                        data: <?php echo json_encode($datos_vistas); ?>,
                        borderColor: '#4361ee',
                        backgroundColor: gradientAzul,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#4361ee',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Sesiones',
                        data: <?php echo json_encode($datos_sesiones); ?>,
                        borderColor: '#2ecc71',
                        backgroundColor: gradientVerde,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#2ecc71',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 25, usePointStyle: true, font: { family: 'Inter', size: 12 } }
                    },
                    tooltip: {
                        padding: 12,
                        backgroundColor: '#1e1e2d',
                        titleFont: { size: 14 },
                        bodyFont: { size: 13 },
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f0f2f5' }, border: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });


    </script>
</body>
</html>