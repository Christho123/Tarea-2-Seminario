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

// Registrar visita a esta página de edición
try {
    registrarVisitaWeb($pdo, 'Editar Empleado', $_SERVER['REQUEST_URI'] ?? '', $_SESSION['user_id']);
} catch (PDOException $e) {}

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
            
            // Refrescar datos para el formulario
            $emp['nombre'] = $nombre;
            $emp['apellido'] = $apellido;
            $emp['email'] = $email;
            $emp['departamento'] = $departamento;
            $emp['cargo'] = $cargo;
            $emp['fecha_ingreso'] = $fecha_ingreso;
            $emp['telefono'] = $telefono;
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
    <title>Editar Empleado - Analytics Pro</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap">
    <style>
        :root {
            --primary: #4361ee;
            --success: #2ecc71;
            --danger: #f64e60;
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

        /* --- CONTENIDO --- */
        .contenido {
            margin-left: var(--sidebar-width);
            flex-grow: 1;
            padding: 2.5rem;
            max-width: 1000px; /* Un poco más angosto para formularios */
        }

        .header-dashboard { 
            margin-bottom: 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .header-dashboard h1 { font-size: 1.8rem; font-weight: 700; }

        /* Mensajes */
        .mensaje {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .mensaje-error { background: #fff5f5; color: var(--danger); border: 1px solid #feb2b2; }
        .mensaje-exito { background: #f0fff4; color: var(--success); border: 1px solid #9ae6b4; }

        /* Contenedor Formulario */
        .contenedor-blanco {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid #edf2f7;
        }

        .grid-form { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .grupo-input { margin-bottom: 1.2rem; display: flex; flex-direction: column; gap: 0.5rem; }
        .grupo-input label { font-size: 0.85rem; font-weight: 600; color: var(--text-main); }
        
        input {
            padding: 0.8rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
        }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1); }

        .form-acciones {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            border-top: 1px solid #eee;
            padding-top: 1.5rem;
        }

        .btn-primario {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }

        .btn-secundario {
            background: #f1f3f9;
            color: #4e5d78;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo">
            <span style="color: var(--primary); margin-right: 5px;">●</span> ANALYTICS
        </div>
        <nav class="menu">
            <a href="dashboard.php" class="menu-item">Dashboard</a>
            <a href="auditoria.php" class="menu-item">Auditoría</a>
            <a href="rrhh.php" class="menu-item activo">RRHH</a>
            <a href="analitica.php" class="menu-item">Web Analítica</a>
        </nav>
        <form action="logout.php" method="POST">
            <button type="submit" class="btn-logout">Cerrar Sesión</button>
        </form>
    </aside>

    <main class="contenido">
        <div class="header-dashboard">
            <h1>Editar Empleado</h1>
            <a href="rrhh.php" class="btn-secundario" style="padding: 0.5rem 1rem; font-size: 0.8rem;">← Volver al Listado</a>
        </div>

        <?php if ($error): ?>
            <div class="mensaje mensaje-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($exito): ?>
            <div class="mensaje mensaje-exito"><?php echo htmlspecialchars($exito); ?></div>
        <?php endif; ?>

        <div class="contenedor-blanco">
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <input type="hidden" name="actualizar" value="1">

                <div class="grid-form">
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
                    <label>Correo Electrónico *</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($emp['email']); ?>" style="width: 100%;">
                </div>

                <div class="grid-form">
                    <div class="grupo-input">
                        <label>Departamento *</label>
                        <input type="text" name="departamento" required value="<?php echo htmlspecialchars($emp['departamento']); ?>">
                    </div>
                    <div class="grupo-input">
                        <label>Cargo *</label>
                        <input type="text" name="cargo" required value="<?php echo htmlspecialchars($emp['cargo']); ?>">
                    </div>
                </div>

                <div class="grid-form">
                    <div class="grupo-input">
                        <label>Fecha de Ingreso *</label>
                        <input type="date" name="fecha_ingreso" required value="<?php echo htmlspecialchars($emp['fecha_ingreso']); ?>">
                    </div>
                    <div class="grupo-input">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" value="<?php echo htmlspecialchars($emp['telefono'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-acciones">
                    <button type="submit" class="btn-primario">Guardar Cambios</button>
                    <a href="rrhh.php" class="btn-secundario">Cancelar</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>