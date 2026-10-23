<?php
session_start();
require_once 'conexion.php';

$id_profesor = $_SESSION['id_profesor'] ?? 1;
$mensaje_exito = '';

// Obtener parámetros por GET o POST
$asignacion_seleccionada = $_GET['asignacion'] ?? ($_POST['asignacion'] ?? null);
$fecha_seleccionada = $_GET['fecha'] ?? ($_POST['fecha'] ?? date('Y-m-d'));

// 1. Obtener materias y grupos asignados al profesor
$query_asig = $pdo->prepare("
    SELECT 
        mp.id AS asignacion_id,
        m.nombre AS materia_nombre,
        g.nombre AS grupo_nombre
    FROM materia_profesor mp
    JOIN materias m ON mp.materia = m.id
    JOIN grupos g ON mp.grupo = g.id
    WHERE mp.profesor = ? AND mp.estado = 1
");
$query_asig->execute([$id_profesor]);
$asignaciones = $query_asig->fetchAll();

// Validar que la asignación pertenezca al profesor si se intenta manipular
if ($asignacion_seleccionada) {
    $val_asig = $pdo->prepare("SELECT id FROM materia_profesor WHERE id = ? AND profesor = ?");
    $val_asig->execute([$asignacion_seleccionada, $id_profesor]);
    if (!$val_asig->fetch()) {
        $asignacion_seleccionada = null;
    }
}

// 2. Procesar el guardado de asistencias (HU-11, HU-12)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_asistencia']) && $asignacion_seleccionada) {
    $inscripciones_ids = $_POST['inscripcion_id'] ?? [];
    $estatus_array = $_POST['estatus'] ?? [];
    $observaciones_array = $_POST['observaciones'] ?? [];

    $stmt_guardar = $pdo->prepare("
        INSERT INTO asistencias (inscripcion, fecha, estatus, observaciones)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            estatus = VALUES(estatus),
            observaciones = VALUES(observaciones)
    ");

    foreach ($inscripciones_ids as $ins_id) {
        $estatus = $estatus_array[$ins_id] ?? 'asistencia';
        $observaciones = !empty($observaciones_array[$ins_id]) ? $observaciones_array[$ins_id] : null;
        
        $stmt_guardar->execute([$ins_id, $fecha_seleccionada, $estatus, $observaciones]);
    }

    $mensaje_exito = "Las asistencias se han guardado y actualizado correctamente.";
}

// 3. Consultar la lista de alumnos y su asistencia previa para la fecha seleccionada (HU-13)
$alumnos = [];
if ($asignacion_seleccionada) {
    $query_alumnos = $pdo->prepare("
        SELECT 
            i.id AS inscripcion_id,
            a.nombre,
            asist.estatus AS estatus_actual,
            asist.observaciones AS observaciones_actual
        FROM inscripciones i
        JOIN alumnos a ON i.alumno = a.id
        LEFT JOIN asistencias asist ON asist.inscripcion = i.id AND asist.fecha = ?
        WHERE i.asignacion = ? AND i.estatus = 'cursando'
        ORDER BY a.nombre ASC
    ");
    $query_alumnos->execute([$fecha_seleccionada, $asignacion_seleccionada]);
    $alumnos = $query_alumnos->fetchAll();
}

// Cargar la vista HTML separada
include 'asistencias.html';
?>