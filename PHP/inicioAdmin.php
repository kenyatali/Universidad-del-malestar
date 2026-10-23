<?php
session_start();
require_once 'conexion.php';

// 1. Validar que exista sesión activa y que el rol sea estrictamente 'administrativo'
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrativo') {
    header('Location: ../HTML/login.html');
    exit();
}

$correoUsuario = $_SESSION['usuario'];
$nombre = $_SESSION['nombre'];
$rol = $_SESSION['rol'];

$totalAlumnos = 0;
$totalDocentes = 0;
$totalCarreras = 0;

try {
    // 2. Consultar métricas generales de la base de datos gestion_escolar
    $totalAlumnos = $pdo->query("SELECT COUNT(*) FROM alumnos WHERE estado = 1")->fetchColumn();
    $totalDocentes = $pdo->query("SELECT COUNT(*) FROM profesores WHERE estado = 1")->fetchColumn();
    $totalCarreras = $pdo->query("SELECT COUNT(*) FROM carreras")->fetchColumn();

} catch (PDOException $e) {
    // Manejo básico en caso de error de consulta
}

// 3. Incluir la vista HTML de la interfaz del administrador
include '../HTML/inicioAdmin.html';
?>