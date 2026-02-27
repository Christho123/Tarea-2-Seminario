<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) {
    header("Location: rrhh.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id = ?");
$stmt->execute([$id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$emp) {
    header("Location: rrhh.php");
    exit();
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
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
            $stmt = $pdo->prepare("UPDATE empleados SET nombre=?, apellido=?, email=?, departamento=?, cargo=?, fecha_ingreso=?, telefono=? WHERE id=?");
            $stmt->execute([$nombre, $apellido, $email, $departamento, $cargo, $fecha_ingreso, $telefono ?: null, $id]);
            registrarAuditoria($pdo, $_SESSION['user_id'], 'EMPLEADO_ACTUALIZADO', "ID: $id - $email");
            $exito = 'Empleado actualizado correctamente.';
            $emp = array_merge($emp, $_POST);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) $error = 'Ya existe otro empleado con ese correo.';
            else $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar empleado - Empresa</title>
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
            <h1>Editar empleado</h1>
            <a href="rrhh.php" class="btn-primario">← Volver al listado</a>
        </div>
        <?php if ($error): ?>
            <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($exito): ?>
            <div class="mensaje-exito"><?php echo htmlspecialchars($exito); ?></div>
        <?php endif; ?>
        <div class="contenedor-tabla">
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <input type="hidden" name="actualizar" value="1">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                    <div class="grupo-input">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" required value="<?php echo htmlspecialchars($emp['nombre']); ?>">
                    </div>
                    <div class="grupo-input">
                        <label>Apellido *</label>
                        <input type="text" name="apellido" required value="<?php echo htmlspecialchars($emp['apellido']); ?>">
                    </div>
                </div>
                <div class="grupo-input">
                    <label>Correo electrónico *</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($emp['email']); ?>">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                    <div class="grupo-input">
                        <label>Departamento *</label>
                        <input type="text" name="departamento" required value="<?php echo htmlspecialchars($emp['departamento']); ?>">
                    </div>
                    <div class="grupo-input">
                        <label>Cargo *</label>
                        <input type="text" name="cargo" required value="<?php echo htmlspecialchars($emp['cargo']); ?>">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                    <div class="grupo-input">
                        <label>Fecha de ingreso *</label>
                        <input type="date" name="fecha_ingreso" required value="<?php echo htmlspecialchars($emp['fecha_ingreso']); ?>">
                    </div>
                    <div class="grupo-input">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" value="<?php echo htmlspecialchars($emp['telefono'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-acciones">
                    <button type="submit" class="btn-primario">Guardar cambios</button>
                    <a href="rrhh.php" class="btn-primario" style="background:#666;">Cancelar</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
