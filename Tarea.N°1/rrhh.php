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
    if (empty($exito)) $error = 'Error al cargar empleados.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RRHH - Gestión de Empleados</title>
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
            transition: 0.3s;
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

        /* Mensajes */
        .mensaje {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .mensaje-error { background: #fff5f5; color: var(--danger); border: 1px solid #feb2b2; }
        .mensaje-exito { background: #f0fff4; color: var(--success); border: 1px solid #9ae6b4; }

        /* Contenedores Blancos */
        .contenedor-blanco {
            background: var(--white);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid #edf2f7;
            margin-bottom: 2rem;
        }

        h2 { font-size: 1.2rem; margin-bottom: 1.5rem; color: var(--text-main); }

        /* Formulario Estilizado */
        .grid-form { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .grupo-input { margin-bottom: 1.2rem; display: flex; flex-direction: column; gap: 0.5rem; }
        .grupo-input label { font-size: 0.85rem; font-weight: 600; color: var(--text-main); }
        
        input {
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }
        input:focus { outline: none; border-color: var(--primary); }

        .btn-primario {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-primario:hover { opacity: 0.9; }

        /* Tabla Estilizada */
        .tabla-datos { width: 100%; border-collapse: collapse; }
        .tabla-datos th {
            text-align: left;
            padding: 1rem;
            background: #fcfcfd;
            color: var(--text-muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            border-bottom: 1px solid #edf2f7;
        }
        .tabla-datos td { padding: 1rem; border-bottom: 1px solid #f8f9fc; font-size: 0.9rem; }
        
        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #e1f0ff;
            color: var(--primary);
        }

        .btn-accion {
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 5px;
            margin-right: 5px;
        }
        .btn-editar { background: #fff8dd; color: #ff9900; }
        .btn-eliminar { background: #fff5f5; color: var(--danger); }

    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo">
            <span style="color: var(--primary); margin-right: 5px;">●</span> RRHH
        </div>
        <nav class="menu">
            <a href="dashboard.php" class="menu-item">Dashboard</a>
            <a href="auditoria.php" class="menu-item">Auditoría</a>
            <a href="rrhh.php" class="menu-item activo">RRHH</a>
            <a href="analitica.php" class="menu-item">Analisis</a>
        </nav>
        <form action="logout.php" method="POST">
            <button type="submit" class="btn-logout">Cerrar Sesión</button>
        </form>
    </aside>

    <main class="contenido">
        <div class="header-dashboard">
            <h1>Gestión de Recursos Humanos</h1>
            <p>Registra y administra la nómina de empleados de la empresa.</p>
        </div>

        <?php if ($error): ?>
            <div class="mensaje mensaje-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($exito): ?>
            <div class="mensaje mensaje-exito"><?php echo htmlspecialchars($exito); ?></div>
        <?php endif; ?>

        <div class="contenedor-blanco">
            <h2>Registrar nuevo empleado</h2>
            <form method="POST" action="">
                <input type="hidden" name="guardar_empleado" value="1">
                
                <div class="grid-form">
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
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" style="width: 100%;">
                </div>

                <div class="grid-form">
                    <div class="grupo-input">
                        <label>Departamento *</label>
                        <input type="text" name="departamento" required placeholder="Ej: TI" value="<?php echo htmlspecialchars($_POST['departamento'] ?? ''); ?>">
                    </div>
                    <div class="grupo-input">
                        <label>Cargo *</label>
                        <input type="text" name="cargo" required placeholder="Ej: Desarrollador" value="<?php echo htmlspecialchars($_POST['cargo'] ?? ''); ?>">
                    </div>
                </div>

                <div class="grid-form">
                    <div class="grupo-input">
                        <label>Fecha de ingreso *</label>
                        <input type="date" name="fecha_ingreso" required value="<?php echo htmlspecialchars($_POST['fecha_ingreso'] ?? date('Y-m-d')); ?>">
                    </div>
                    <div class="grupo-input">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" placeholder="Opcional" value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
                    </div>
                </div>

                <div style="margin-top: 1rem;">
                    <button type="submit" class="btn-primario">Guardar Empleado</button>
                </div>
            </form>
        </div>

        <div class="contenedor-blanco">
            <h2>Listado de empleados</h2>
            <?php if (empty($empleados)): ?>
                <p style="color:var(--text-muted);">No hay empleados registrados actualmente.</p>
            <?php else: ?>
                <table class="tabla-datos">
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Departamento</th>
                            <th>Cargo</th>
                            <th>Ingreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $emp): ?>
                        <tr>
                            <td style="font-weight: 600;">
                                <?php echo htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($emp['departamento']); ?></span></td>
                            <td><?php echo htmlspecialchars($emp['cargo']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($emp['fecha_ingreso'])); ?></td>
                            <td>
                                <a href="rrhh_editar.php?id=<?php echo (int)$emp['id']; ?>" class="btn-accion btn-editar">Editar</a>
                                <a href="rrhh_eliminar.php?id=<?php echo (int)$emp['id']; ?>" class="btn-accion btn-eliminar" onclick="return confirm('¿Eliminar este empleado?');">Eliminar</a>
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