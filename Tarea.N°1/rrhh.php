<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

try {
    registrarVisitaWeb($pdo, 'Recursos Humanos', $_SERVER['REQUEST_URI'] ?? '', $_SESSION['user_id']);
} catch (PDOException $e) {}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_empleado'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $fecha_ingreso = $_POST['fecha_ingreso'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');

    if (!$nombre || !$apellido || !$email || !$departamento || !$cargo || !$fecha_ingreso) {
        $error = 'Complete todos los campos obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico no válido.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO empleados (nombre, apellido, email, departamento, cargo, fecha_ingreso, telefono, creado_por_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $apellido, $email, $departamento, $cargo, $fecha_ingreso, $telefono ?: null, $_SESSION['user_id']]);
            registrarAuditoria($pdo, $_SESSION['user_id'], 'EMPLEADO_REGISTRADO', "Empleado: $email");
            $exito = 'Empleado registrado correctamente.';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) $error = 'Ya existe un empleado con ese correo.';
            else $error = 'Error al guardar: ' . $e->getMessage();
        }
    }
}

$empleados = [];
try {
    $stmt = $pdo->query("SELECT e.*, u.email AS creado_por_email FROM empleados e LEFT JOIN users u ON e.creado_por_user_id = u.id ORDER BY e.created_at DESC");
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if (empty($exito)) $error = 'Error al cargar empleados. Ejecute database/crear_base_datos.sql si no lo ha hecho.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recursos Humanos - Empresa</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="dashboard">
    <aside class="sidebar">
        <div class="logo">EMPRESA</div>
        <nav class="menu">
            <a href="dashboard.php" class="menu-item">Inicio</a>
            <a href="auditoria.php" class="menu-item">Auditoría</a>
            <a href="rrhh.php" class="menu-item activo">Recursos Humanos</a>
            <a href="analitica.php" class="menu-item">Analítica Web</a>
        </nav>
        <form action="logout.php" method="POST">
            <button type="submit" class="btn-logout">Cerrar Sesión</button>
        </form>
    </aside>
    <main class="contenido">
        <div class="header-dashboard">
            <h1>Módulo Recursos Humanos (CRM)</h1>
            <p class="info-usuario">El administrador registra empleados desde este módulo.</p>
        </div>

        <?php if ($error): ?>
            <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($exito): ?>
            <div class="mensaje-exito"><?php echo htmlspecialchars($exito); ?></div>
        <?php endif; ?>

        <div class="contenedor-tabla">
            <h2>Registrar nuevo empleado</h2>
            <form method="POST" action="">
                <input type="hidden" name="guardar_empleado" value="1">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                    <div class="grupo-input">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" required value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                    </div>
                    <div class="grupo-input">
                        <label>Apellido *</label>
                        <input type="text" name="apellido" required value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>">
                    </div>
                </div>
                <div class="grupo-input">
                    <label>Correo electrónico *</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                    <div class="grupo-input">
                        <label>Departamento *</label>
                        <input type="text" name="departamento" required placeholder="Ej: Ventas, TI" value="<?php echo htmlspecialchars($_POST['departamento'] ?? ''); ?>">
                    </div>
                    <div class="grupo-input">
                        <label>Cargo *</label>
                        <input type="text" name="cargo" required placeholder="Ej: Analista" value="<?php echo htmlspecialchars($_POST['cargo'] ?? ''); ?>">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                    <div class="grupo-input">
                        <label>Fecha de ingreso *</label>
                        <input type="date" name="fecha_ingreso" required value="<?php echo htmlspecialchars($_POST['fecha_ingreso'] ?? date('Y-m-d')); ?>">
                    </div>
                    <div class="grupo-input">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" placeholder="Opcional" value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-acciones">
                    <button type="submit" class="btn-primario">Guardar empleado</button>
                </div>
            </form>
        </div>

        <div class="contenedor-tabla">
            <h2>Listado de empleados</h2>
            <?php if (empty($empleados)): ?>
                <p style="color:#666;">No hay empleados registrados. Use el formulario superior para registrar.</p>
            <?php else: ?>
                <table class="tabla-datos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Email</th>
                            <th>Departamento</th>
                            <th>Cargo</th>
                            <th>F. ingreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $emp): ?>
                        <tr>
                            <td><?php echo (int)$emp['id']; ?></td>
                            <td><?php echo htmlspecialchars($emp['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($emp['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                            <td><?php echo htmlspecialchars($emp['departamento']); ?></td>
                            <td><?php echo htmlspecialchars($emp['cargo']); ?></td>
                            <td><?php echo htmlspecialchars($emp['fecha_ingreso']); ?></td>
                            <td>
                                <a href="rrhh_editar.php?id=<?php echo (int)$emp['id']; ?>" class="btn-editar">Editar</a>
                                <a href="rrhh_eliminar.php?id=<?php echo (int)$emp['id']; ?>" class="btn-eliminar" onclick="return confirm('¿Eliminar este empleado?');">Eliminar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
