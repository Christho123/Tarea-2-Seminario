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
    
    // Intentar enviar correo, pero si falla, no importa
    try {
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'christianchirinos11@gmail.com';
        $mail->Password = 'wlsq nbyr zbly gyvs';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom('christianchirinos11@gmail.com', 'Sistema de Seguridad');
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = "Codigo de Verificacion: $otp";
        $mail->Body = "
        <div style='font-family:sans-serif;border:1px solid #ddd;padding:20px;border-radius:10px;max-width:400px;margin:0 auto;'>
            <h2 style='color:#1e3a5f;'>Verificacion de Acceso</h2>
            <p>Tu codigo de verificacion es:</p>
            <h1 style='color:#6366f1;letter-spacing:8px;font-size:42px;text-align:center;margin:20px 0;'>$otp</h1>
            <p style='color:#666;'>Este codigo expira en <strong>5 minutos</strong>.</p>
        </div>";
        
        $mail->send();
        
    } catch (Exception $e) {
        // Ignorar error de correo en desarrollo
    }
    
    // Siempre retornar éxito en desarrollo
    return ['status' => 'success', 'message' => 'OTP generado (revisa otp_debug.txt)'];
}
?>