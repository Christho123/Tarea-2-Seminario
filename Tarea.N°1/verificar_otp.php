<?php
// CONFIGURACIÓN DE SESIONES
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

session_start();
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

// Verificar que venga de la fase 1
if (!isset($_SESSION['temp_user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Recibir los 6 dígitos
    $otp_input = '';

    if (isset($_POST['otp'])) {
        $otp_input = $_POST['otp'];
    } else {
        for ($i = 1; $i <= 6; $i++) {
            $otp_input .= $_POST["otp_$i"] ?? '';
        }
    }

    $user_id = $_SESSION['temp_user_id'];
    $email   = $_SESSION['temp_email'];

    // Validar OTP
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

        // Marcar como usado
        $stmt = $pdo->prepare("UPDATE otp_codes SET used = 1 WHERE id = ?");
        $stmt->execute([$otp_data['id']]);

        // Iniciar sesión definitiva
        $_SESSION['user_id'] = $user_id;
        $_SESSION['email']   = $email;

        // Limpiar temporales
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['temp_email']);

        // Auditoría
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
<title>Verificación OTP - Fase 2</title>
<link rel="stylesheet" href="css/estilos.css">

<style>
.otp-inputs {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin: 20px 0;
}
.otp-inputs input {
    width: 50px;
    height: 60px;
    text-align: center;
    font-size: 24px;
    font-weight: bold;
    border: 2px solid #ddd;
    border-radius: 8px;
}
.otp-inputs input:focus {
    border-color: #6366f1;
    outline: none;
}
.info-otp {
    background: #e0e7ff;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
    color: #1e3a5f;
}
.error-mensaje {
    background: #ffe0e0;
    color: #b00020;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    text-align: center;
}
</style>
</head>

<body class="login-page">
<div class="contenedor-login">

<h2>Verificación de Dos Factores</h2>
<p style="color:#666;text-align:center;margin-bottom:20px;font-size:14px;">
Fase 2: Ingrese el código de 6 dígitos
</p>

<div class="info-otp">
📧 Se envió un código a:<br>
<strong><?php echo htmlspecialchars($_SESSION['temp_email']); ?></strong>
</div>

<?php
// Mostrar OTP en localhost (modo desarrollo)
$otp_mostrar = '';

if (
    in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) ||
    (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
) {
    $stmt = $pdo->prepare("SELECT code FROM otp_codes 
                           WHERE user_id = ? 
                           AND used = 0 
                           AND expires_at > NOW() 
                           ORDER BY id DESC 
                           LIMIT 1");
    $stmt->execute([$_SESSION['temp_user_id']]);
    $otp_mostrar = $stmt->fetchColumn();
}

if ($otp_mostrar):
?>

<div style="background:#fff3cd;border:1px solid #ffc107;padding:12px;border-radius:8px;margin-bottom:20px;text-align:center;">
<strong>Modo desarrollo:</strong><br>
Tu código es:
<strong style="font-size:24px;letter-spacing:4px;">
<?php echo htmlspecialchars($otp_mostrar); ?>
</strong>
</div>

<?php endif; ?>

<?php if ($error): ?>
<div class="error-mensaje">
<?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<form method="POST" action="" id="otpForm">

<div class="otp-inputs">
<?php for ($i = 1; $i <= 6; $i++): ?>
<input type="text"
       name="otp_<?php echo $i; ?>"
       maxlength="1"
       required
       onkeyup="moveNext(this, <?php echo $i; ?>)"
       oninput="this.value=this.value.replace(/[^0-9]/g,'')">
<?php endfor; ?>
</div>

<button type="submit" class="btn-primario">Verificar Código</button>

</form>

<a href="index.php" class="btn-secundario">Cancelar / Volver</a>

<div class="footer-login">
El código expira en 5 minutos
</div>

</div>

<script>
function moveNext(input, index) {
    if (input.value.length === 1) {
        const next = document.querySelector(`input[name="otp_${index + 1}"]`);
        if (next) next.focus();
    }
    if (event.key === 'Backspace' && input.value.length === 0) {
        const prev = document.querySelector(`input[name="otp_${index - 1}"]`);
        if (prev) prev.focus();
    }
}

document.querySelectorAll('.otp-inputs input').forEach(input => {
    input.addEventListener('input', () => {
        const values = Array.from(document.querySelectorAll('.otp-inputs input'))
            .map(i => i.value).join('');
        if (values.length === 6) {
            document.getElementById('otpForm').submit();
        }
    });
});
</script>

</body>
</html>