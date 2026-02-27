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
$por_pagina = [];
$ultimas_visitas = [];

try {
    $total_visitas = $pdo->query("SELECT COUNT(*) FROM visitas_web")->fetchColumn();
    $visitas_hoy = $pdo->query("SELECT COUNT(*) FROM visitas_web WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $stmt = $pdo->query("SELECT pagina, COUNT(*) AS total FROM visitas_web GROUP BY pagina ORDER BY total DESC LIMIT 10");
    $por_pagina = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->query("SELECT v.*, u.email FROM visitas_web v LEFT JOIN users u ON v.user_id = u.id ORDER BY v.created_at DESC LIMIT 50");
    $ultimas_visitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analítica Web - Empresa</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="dashboard">
    <aside class="sidebar">
        <div class="logo">EMPRESA</div>
        <nav class="menu">
            <a href="dashboard.php" class="menu-item">Inicio</a>
            <a href="auditoria.php" class="menu-item">Auditoría</a>
            <a href="rrhh.php" class="menu-item">Recursos Humanos</a>
            <a href="analitica.php" class="menu-item activo">Analítica Web</a>
        </nav>
        <form action="logout.php" method="POST">
            <button type="submit" class="btn-logout">Cerrar Sesión</button>
        </form>
    </aside>
    <main class="contenido">
        <div class="header-dashboard">
            <h1>Módulo Analítica Web (CRM)</h1>
            <p class="info-usuario">Estadísticas de visitas y uso del sistema.</p>
        </div>

        <div class="tarjetas-dashboard">
            <div class="tarjeta">
                <h3>Total de visitas</h3>
                <div class="valor"><?php echo (int)$total_visitas; ?></div>
            </div>
            <div class="tarjeta">
                <h3>Visitas hoy</h3>
                <div class="valor"><?php echo (int)$visitas_hoy; ?></div>
            </div>
            <div class="tarjeta">
                <h3>Páginas distintas</h3>
                <div class="valor"><?php echo count($por_pagina); ?></div>
            </div>
        </div>

        <div class="contenedor-tabla">
            <h2>Visitas por página</h2>
            <?php if (empty($por_pagina)): ?>
                <p style="color:#666;">No hay datos de visitas. Navega por el sistema para generarlos.</p>
            <?php else: ?>
                <table class="tabla-datos">
                    <thead>
                        <tr>
                            <th>Página</th>
                            <th>Visitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($por_pagina as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['pagina']); ?></td>
                            <td><?php echo (int)$row['total']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="contenedor-tabla">
            <h2>Últimas visitas</h2>
            <?php if (empty($ultimas_visitas)): ?>
                <p style="color:#666;">No hay registros recientes.</p>
            <?php else: ?>
                <table class="tabla-datos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Página</th>
                            <th>IP</th>
                            <th>Fecha/Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimas_visitas as $v): ?>
                        <tr>
                            <td><?php echo (int)$v['id']; ?></td>
                            <td><?php echo htmlspecialchars($v['email'] ?? 'Anónimo'); ?></td>
                            <td><?php echo htmlspecialchars($v['pagina']); ?></td>
                            <td><?php echo htmlspecialchars($v['ip_address'] ?? '-'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($v['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
