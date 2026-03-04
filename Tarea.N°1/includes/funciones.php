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

function obtenerIPReal() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Captura la IP detrás de proxies o Cloudflare
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return ($ip === '::1') ? '127.0.0.1' : $ip;
}

// Función de registro actualizada
function registrarVisitaWeb($pdo, $pagina, $url, $user_id) {
    $ip = obtenerIPReal();
    $stmt = $pdo->prepare("INSERT INTO visitas_web (pagina, url, user_id, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$pagina, $url, $user_id, $ip]);
}

?>