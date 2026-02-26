<?php
require_once 'conexion.php';

function registrarAuditoria($pdo, $user_id, $action, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address, details) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $ip, $details]);
}

function verificarIntentosFallidos($pdo, $email) {
    // Contar intentos fallidos en los últimos 15 minutos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs 
                          WHERE action = 'LOGIN_ATTEMPT_FAILED' 
                          AND details LIKE ? 
                          AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->execute(["%$email%"]);
    return $stmt->fetchColumn();
}
?>