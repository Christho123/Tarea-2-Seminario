<?php
// CONFIGURACIÓN DE SESIONES
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

session_start();
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

if (!isset($_SESSION['temp_user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp_input = '';
    for ($i = 1; $i <= 6; $i++) {
        $otp_input .= $_POST["otp_$i"] ?? '';
    }

    $user_id = $_SESSION['temp_user_id'];
    $email   = $_SESSION['temp_email'];

    $stmt = $pdo->prepare("SELECT * FROM otp_codes 
                           WHERE user_id = ? 
                           AND code = ? 
                           AND used = 0 
                           AND expires_at > NOW() 
                           ORDER BY id DESC 
                           LIMIT 1");

    $stmt->execute([$user_id, $otp_input]);
    $otp_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($otp_data) {
        $stmt = $pdo->prepare("UPDATE otp_codes SET used = 1 WHERE id = ?");
        $stmt->execute([$otp_data['id']]);

        $_SESSION['user_id'] = $user_id;
        $_SESSION['email']   = $email;

        unset($_SESSION['temp_user_id']);
        unset($_SESSION['temp_email']);

        registrarAuditoria($pdo, $user_id, 'OTP_VERIFIED_SUCCESS', "Email: $email");

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Código incorrecto o expirado";
        registrarAuditoria($pdo, $user_id, 'OTP_VERIFIED_FAILED', "Email: $email - Ingresado: $otp_input");
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguridad - Verificación OTP</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap">
    <style>
        :root {
            --primary: #4361ee;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --white: #ffffff;
            --text-main: #1e1e2d;
            --text-muted: #b5b5c3;
            --danger: #f64e60;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
        }

        .contenedor-login {
            background: var(--white);
            width: 100%;
            max-width: 450px;
            padding: 3rem 2.5rem;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            text-align: center;
        }

        .icon-otp {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            background: #f0f3ff;
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            display: inline-block;
            color: var(--primary);
        }

        h2 { font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem; }
        .subtitle { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 2rem; }

        .info-email {
            background: #f8fafc;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 2rem;
            border: 1px dashed #cbd5e1;
            font-size: 0.9rem;
        }

        /* Cuadros OTP */
        .otp-inputs {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .otp-inputs input {
            width: 50px;
            height: 65px;
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
            transition: all 0.3s ease;
        }

        .otp-inputs input:focus {
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
            outline: none;
        }

        /* Modo Desarrollo */
        .dev-badge {
            background: #fff9db;
            color: #856404;
            padding: 10px;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-bottom: 1.5rem;
            border: 1px solid #ffeeba;
        }

        .mensaje-error {
            background: #fff5f5;
            color: var(--danger);
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .btn-primario {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primario:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(67, 97, 238, 0.25); }

        .btn-secundario {
            display: inline-block;
            margin-top: 1.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .btn-secundario:hover { color: var(--primary); }

        .footer-login { margin-top: 2rem; color: var(--text-muted); font-size: 0.75rem; font-weight: 500; }
    </style>
</head>
<body>

<div class="contenedor-login">
    <div class="icon-otp">🛡️</div>
    <h2>Verificación 2FA</h2>
    <p class="subtitle">Ingresa el código enviado a tu bandeja</p>

    <div class="info-email">
        Enviado a: <strong><?php echo htmlspecialchars($_SESSION['temp_email']); ?></strong>
    </div>

    <?php
    // Código para modo desarrollo (localhost)
    $otp_mostrar = '';
    if (in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) {
        $stmt = $pdo->prepare("SELECT code FROM otp_codes WHERE user_id = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$_SESSION['temp_user_id']]);
        $otp_mostrar = $stmt->fetchColumn();
    }
    if ($otp_mostrar):
    ?>
    <div class="dev-badge">
        <strong>Modo Desarrollo:</strong> Tu código es <code><?php echo htmlspecialchars($otp_mostrar); ?></code>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="otpForm">
        <div class="otp-inputs">
            <?php for ($i = 1; $i <= 6; $i++): ?>
            <input type="text" name="otp_<?php echo $i; ?>" id="otp_<?php echo $i; ?>" 
                   maxlength="1" required autocomplete="off"
                   onkeyup="moveNext(this, <?php echo $i; ?>)"
                   oninput="this.value=this.value.replace(/[^0-9]/g,'')">
            <?php endfor; ?>
        </div>

        <button type="submit" class="btn-primario">Verificar Identidad</button>
    </form>

    <a href="index.php" class="btn-secundario">← Volver al inicio</a>

    <div class="footer-login">
        ESTE CÓDIGO EXPIRA EN 5 MINUTOS
    </div>
</div>

<script>
    // Autofocus al primer campo
    document.getElementById('otp_1').focus();

    function moveNext(input, index) {
        // Al escribir, mover al siguiente
        if (input.value.length === 1 && index < 6) {
            document.getElementById(`otp_${index + 1}`).focus();
        }
        // Al borrar con Backspace, volver al anterior
        if (event.key === 'Backspace' && input.value.length === 0 && index > 1) {
            document.getElementById(`otp_${index - 1}`).focus();
        }
    }

    // Pegado inteligente (Paste)
    document.getElementById('otp_1').addEventListener('paste', e => {
        const data = e.clipboardData.getData('text').trim();
        if (data.length === 6 && /^\d+$/.test(data)) {
            for (let i = 0; i < 6; i++) {
                document.getElementById(`otp_${i+1}`).value = data[i];
            }
            document.getElementById('otpForm').submit();
        }
    });

    // Envío automático al llenar el último campo
    document.getElementById('otp_6').addEventListener('input', function() {
        if (this.value.length === 1) {
            // Un pequeño delay para que el usuario vea el último dígito
            setTimeout(() => document.getElementById('otpForm').submit(), 100);
        }
    });
</script>

</body>
</html>