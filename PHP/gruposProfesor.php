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

// Consulta para obtener los grupos asignados al profesor
$query_grupos = $pdo->prepare("
    SELECT 
        mp.id AS asignacion_id,
        m.nombre AS materia_nombre,
        m.clave AS materia_clave,
        g.id AS grupo_id,
        g.nombre AS grupo_nombre,
        g.cupo_maximo,
        COUNT(i.id) AS total_alumnos
    FROM materia_profesor mp
    JOIN materias m ON mp.materia = m.id
    JOIN grupos g ON mp.grupo = g.id
    LEFT JOIN inscripciones i ON i.asignacion = mp.id AND i.estatus = 'cursando'
    WHERE mp.profesor = ? AND mp.estado = 1
    GROUP BY mp.id, m.nombre, m.clave, g.id, g.nombre, g.cupo_maximo
");
$query_grupos->execute([$id_profesor]);
$grupos = $query_grupos->fetchAll();

// Cargar la vista HTML separada
include __DIR__ . '/../HTML/gruposProfesor.html';
?>