<?php
// CONFIGURACIÓN DE SESIONES - ANTES DE session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

session_start();
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';


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
    <title>Auditoría - Empresa</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="dashboard">
    <aside class="sidebar">
        <div class="logo">EMPRESA</div>
        <nav class="menu">
            <a href="auditoria.php" class="menu-item activo">Auditoría</a>
            <a href="rrhh.php" class="menu-item">Recursos Humanos (CRM)</a>
            <a href="analitica.php" class="menu-item">Analítica Web</a>
        </nav>
        <form action="logout.php" method="POST">
            <button type="submit" class="btn-logout">Cerrar Sesión</button>
        </form>
    </aside>
    
    <main class="contenido">
        <div class="contenedor-tabla">
            <h2>Registro de Auditoría</h2>
            <div class="estado-conexion">
                Servidor: localhost | Base de datos: Conectada ✓
            </div>
            
            <table class="tabla-auditoria">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>IP</th>
                        <th>Detalles</th>
                        <th>Fecha/Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $log): ?>
                    <tr>
                        <td><?php echo $log['id']; ?></td>
                        <td><?php echo htmlspecialchars($log['email'] ?? 'Sistema'); ?></td>
                        <td class="<?php echo strpos($log['action'], 'FAILED') !== false ? 'estado-fallo' : 'estado-exito'; ?>">
                            <?php echo $log['action']; ?>
                        </td>
                        <td><?php echo $log['ip_address']; ?></td>
                        <td><?php echo htmlspecialchars($log['details']); ?></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>