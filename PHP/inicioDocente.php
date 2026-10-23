<?php
session_start();
require_once 'conexion.php';

// 1. Validar que exista sesión activa y que el rol sea estrictamente 'docente'
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'docente') {
    header('Location: ../HTML/login.html');
    exit();
}

$correoUsuario = $_SESSION['usuario'];
$nombre = $_SESSION['nombre'];
$rol = $_SESSION['rol'];

$totalMaterias = 0;
$totalGrupos = 0;

try {
    // 2. Obtener el ID del profesor logueado mediante su correo institucional
    $stmt = $pdo->prepare("SELECT id FROM profesores WHERE correo_institucional = ?");
    $stmt->execute([$correoUsuario]);
    $profesor = $stmt->fetch();

    if ($profesor) {
        $idProfesor = $profesor['id'];

        // Ejemplo de consulta para contar materias/grupos asignados (ajusta según tus nombres de tablas reales)
        // Si tienes una tabla de asignaciones o clases, puedes consultarla aquí:
        /*
        $stmtMaterias = $pdo->prepare("SELECT COUNT(*) AS total FROM asignaciones_profesor WHERE id_profesor = ?");
        $stmtMaterias->execute([$idProfesor]);
        $resultado = $stmtMaterias->fetch();
        $totalMaterias = $resultado['total'] ?? 0;
        */
    }
} catch (PDOException $e) {
    // Manejo silencioso en caso de error de consulta
}

// 3. Incluir la vista HTML de la interfaz del docente
include '../HTML/inicioDocente.html';
?>