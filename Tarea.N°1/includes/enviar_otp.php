<?php
require_once 'conexion.php';
require_once 'funciones.php';

// Carga manual de PHPMailer
require __DIR__ . '/../phpmailer/src/Exception.php';
require __DIR__ . '/../phpmailer/src/PHPMailer.php';
require __DIR__ . '/../phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generarYEnviarOTP($pdo, $user_id, $email) {
    // Generar OTP de 6 dígitos
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Calcular expiración (+5 minutos)
    $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // Guardar en base de datos
    $stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $otp, $expires_at]);
    
    // ============================================
    // MODO DESARROLLO: Guardar en archivo en vez de enviar correo
    // ============================================
    $log_file = dirname(__DIR__) . '/otp_debug.txt';
    $contenido = "========================================\n";
    $contenido .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
    $contenido .= "Para: $email\n";
    $contenido .= "OTP: $otp\n";
    $contenido .= "Expira: $expires_at\n";
    $contenido .= "========================================\n\n";
    
    file_put_contents($log_file, $contenido, FILE_APPEND);
    
    // Enviar correo solo si existe config_email.php con tus datos
    $config_file = __DIR__ . '/config_email.php';
    if (file_exists($config_file)) {
        $config = include $config_file;
        if (!empty($config['enviar_correo']) && !empty($config['smtp_user']) && !empty($config['smtp_password'])) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $config['smtp_host'] ?? 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = $config['smtp_user'];
                $mail->Password   = $config['smtp_password'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $config['smtp_port'] ?? 587;
                $mail->CharSet    = 'UTF-8';
                $mail->setFrom($config['from_email'] ?? $config['smtp_user'], $config['from_nombre'] ?? 'Sistema');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = "Código de verificación: $otp";
                $mail->Body = "
                <div style='font-family:sans-serif;border:1px solid #ddd;padding:20px;border-radius:10px;max-width:400px;margin:0 auto;'>
                    <h2 style='color:#1e3a5f;'>Verificación de acceso</h2>
                    <p>Tu código de verificación es:</p>
                    <h1 style='color:#6366f1;letter-spacing:8px;font-size:42px;text-align:center;margin:20px 0;'>$otp</h1>
                    <p style='color:#666;'>Expira en <strong>5 minutos</strong>.</p>
                </div>";
                $mail->send();
            } catch (Exception $e) {
                // Fallo silencioso; el código sigue en otp_debug.txt y se muestra en pantalla en localhost
            }
        }
    }
    
    // Siempre retornar éxito en desarrollo
    return ['status' => 'success', 'message' => 'OTP generado'];
}
?>