<?php
session_start();
require_once 'conexion.php';

// Obtener el ID del profesor autenticado (por defecto 1 para pruebas)
$id_profesor = $_SESSION['id_profesor'] ?? 1;

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
include 'horario.html';
?>