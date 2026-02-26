<?php
session_start();
require_once 'includes/funciones.php';
require_once 'includes/conexion.php';

// Registrar cierre de sesión antes de destruir
if(isset($_SESSION['user_id'])) {
    registrarAuditoria($pdo, $_SESSION['user_id'], 'LOGOUT', "Usuario cerró sesión");
}

session_destroy();
header("Location: index.php");
exit();
?>