<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM empleados WHERE id = ?");
    $stmt->execute([$id]);
    registrarAuditoria($pdo, $_SESSION['user_id'], 'EMPLEADO_ELIMINADO', "ID empleado: $id");
}
header("Location: rrhh.php");
exit();
