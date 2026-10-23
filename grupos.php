<?php
session_start();
require_once 'conexion.php';

$id_profesor = $_SESSION['id_profesor'] ?? 1;

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
include 'grupos.html';
?>