<?php
session_start();
require_once 'conexion.php';

$id_profesor = $_SESSION['id_profesor'] ?? 1;

// Parámetros de filtrado
$tipo_reporte = $_GET['tipo_reporte'] ?? 'asistencias';
$asignacion_seleccionada = $_GET['asignacion'] ?? null;
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');

// 1. Obtener asignaciones autorizadas del profesor
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

// Validar pertenencia de la asignación
if ($asignacion_seleccionada) {
    $val_asig = $pdo->prepare("SELECT id FROM materia_profesor WHERE id = ? AND profesor = ?");
    $val_asig->execute([$asignacion_seleccionada, $id_profesor]);
    if (!$val_asig->fetch()) {
        $asignacion_seleccionada = null;
    }
}

// 2. Ejecutar consulta de reporte según tipo y filtros
$resultados = [];
if ($asignacion_seleccionada) {
    if ($tipo_reporte == 'asistencias') {
        $stmt = $pdo->prepare("
            SELECT 
                ast.fecha,
                a.nombre AS alumno_nombre,
                ast.estatus,
                ast.observaciones
            FROM asistencias ast
            JOIN inscripciones i ON ast.inscripcion = i.id
            JOIN alumnos a ON i.alumno = a.id
            WHERE i.asignacion = ? AND ast.fecha BETWEEN ? AND ?
            ORDER BY ast.fecha DESC, a.nombre ASC
        ");
        $stmt->execute([$asignacion_seleccionada, $fecha_inicio, $fecha_fin]);
        $resultados = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                a.nombre AS alumno_nombre,
                c.parcial1,
                c.parcial2,
                c.parcial3,
                c.calificacion_final,
                i.estatus
            FROM inscripciones i
            JOIN alumnos a ON i.alumno = a.id
            LEFT JOIN calificaciones c ON c.inscripcion = i.id
            WHERE i.asignacion = ?
            ORDER BY a.nombre ASC
        ");
        $stmt->execute([$asignacion_seleccionada]);
        $resultados = $stmt->fetchAll();
    }
}

// 3. Exportar reporte a CSV (HU-20)
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $asignacion_seleccionada && !empty($resultados)) {
    $filename = "reporte_" . $tipo_reporte . "_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    // Bom para UTF-8 en Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if ($tipo_reporte == 'asistencias') {
        fputcsv($output, ['Fecha', 'Alumno', 'Estatus', 'Observaciones']);
        foreach ($resultados as $row) {
            fputcsv($output, [$row['fecha'], $row['alumno_nombre'], $row['estatus'], $row['observaciones'] ?? '']);
        }
    } else {
        fputcsv($output, ['Alumno', 'Parcial 1', 'Parcial 2', 'Parcial 3', 'Calificacion Final', 'Estatus']);
        foreach ($resultados as $row) {
            fputcsv($output, [
                $row['alumno_nombre'],
                $row['parcial1'] ?? 'N/A',
                $row['parcial2'] ?? 'N/A',
                $row['parcial3'] ?? 'N/A',
                $row['calificacion_final'] ?? 0,
                $row['estatus']
            ]);
        }
    }
    fclose($output);
    exit();
}

// Cargar la vista HTML separada
include 'reportes.html';
?>