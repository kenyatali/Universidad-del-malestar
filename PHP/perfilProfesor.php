<?php
session_start();
require_once __DIR__ . '/conexion.php';

if (!isset($_SESSION['usuario'], $_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
    header('Location: ../HTML/login.html');
    exit();
}

$stmtSesion = $pdo->prepare("SELECT id FROM profesores WHERE correo_institucional = ? AND estado = 1");
$stmtSesion->execute([$_SESSION['usuario']]);
$id_profesor = (int)($stmtSesion->fetchColumn() ?: 0);
if ($id_profesor <= 0) {
    session_unset();
    session_destroy();
    header('Location: ../HTML/login.html');
    exit();
}
$mensaje_exito = '';

// 1. Procesar la actualización del teléfono (HU-22)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $telefono = trim($_POST['telefono'] ?? '');
    
    $stmt_update = $pdo->prepare("UPDATE profesores SET telefono = ? WHERE id = ?");
    $stmt_update->execute([$telefono, $id_profesor]);
    
    $mensaje_exito = "Tus datos de contacto se han actualizado correctamente.";
}

// 2. Obtener la información completa del profesor autenticado (HU-21)
$stmt_prof = $pdo->prepare("SELECT * FROM profesores WHERE id = ?");
$stmt_prof->execute([$id_profesor]);
$profesor = $stmt_prof->fetch();

if (!$profesor) {
    session_destroy();
    header('Location: ../HTML/login.html');
    exit();
}

// Cargar la vista HTML separada
include __DIR__ . '/../HTML/perfilProfesor.html';
?>