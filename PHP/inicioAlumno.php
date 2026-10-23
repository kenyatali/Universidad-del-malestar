<?php
session_start();
require_once 'conexion.php';

// 1. Validar que exista sesión activa y que el rol sea estrictamente 'alumno'
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'alumno') {
    header('Location: ../HTML/login.html');
    exit();
}

$correoUsuario = $_SESSION['usuario'];
$nombre = $_SESSION['nombre'];
$rol = $_SESSION['rol'];

$creditos = 0;
$carrera = 'No asignada';

try {
    // 2. Consultar al alumno y su carrera asociada en la base de datos
    $stmt = $pdo->prepare("SELECT * FROM alumnos WHERE correo_institucional = ?");
    $stmt->execute([$correoUsuario]);
    $datosAlumno = $stmt->fetch();

    if ($datosAlumno) {
        $creditos = $datosAlumno['credito'] ?? $datosAlumno['creditos'] ?? 0;
        
        if (isset($datosAlumno['id_carrera'])) {
            $stmtCarrera = $pdo->prepare("SELECT nombre FROM carreras WHERE id = ?");
            $stmtCarrera->execute([$datosAlumno['id_carrera']]);
            $resCarrera = $stmtCarrera->fetch();
            if ($resCarrera) {
                $carrera = $resCarrera['nombre'];
            }
        }
    }
} catch (PDOException $e) {
    $carrera = 'Error al cargar datos';
}

// 3. Como este PHP está en la carpeta PHP/, salimos un nivel con '../' para entrar a HTML y cargar la vista visual
include '../HTML/inicioAlumno.html';
?>