<?php
session_start();
require_once __DIR__ . '/conexion.php';

// Obtener el ID del profesor autenticado (por defecto 1 para pruebas)
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

// Consulta para obtener los horarios exclusivos del profesor logueado
$query_horarios = $pdo->prepare("
    SELECT 
        h.dia, 
        h.hora_inicio, 
        h.hora_fin, 
        h.aula, 
        m.nombre AS materia, 
        g.nombre AS grupo
    FROM horarios h
    JOIN materia_profesor mp ON h.asignacion = mp.id
    JOIN materias m ON mp.materia = m.id
    JOIN grupos g ON mp.grupo = g.id
    WHERE mp.profesor = ? AND mp.estado = 1
    ORDER BY 
        FIELD(h.dia, 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'),
        h.hora_inicio ASC
");
$query_horarios->execute([$id_profesor]);
$horarios = $query_horarios->fetchAll();

// Cargar la vista HTML separada
include __DIR__ . '/../HTML/horarioProfesor.html';
?>