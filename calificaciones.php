<?php
session_start();
require_once 'conexion.php';

$id_profesor = $_SESSION['id_profesor'] ?? 1;
$mensaje_exito = '';
$mensaje_error = '';

$asignacion_seleccionada = $_GET['asignacion'] ?? ($_POST['asignacion'] ?? null);

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

// Validar pertenencia de la asignación
if ($asignacion_seleccionada) {
    $val_asig = $pdo->prepare("SELECT id FROM materia_profesor WHERE id = ? AND profesor = ?");
    $val_asig->execute([$asignacion_seleccionada, $id_profesor]);
    if (!$val_asig->fetch()) {
        $asignacion_seleccionada = null;
    }
}

// Función auxiliar para validar restricciones de parcial (70-100 o 'N/A' o vacío)
function validarParcial($val) {
    $val = trim($val);
    if ($val === '' || strtoupper($val) === 'N/A') {
        return ($val === '') ? null : 'N/A';
    }
    if (preg_match('/^(7[0-9]|8[0-9]|9[0-9]|100)$/', $val)) {
        return $val;
    }
    return false; // No válido
}

// 2. Procesar la captura y actualización de calificaciones (HU-15, HU-16)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_calificaciones']) && $asignacion_seleccionada) {
    $inscripciones_ids = $_POST['inscripcion_id'] ?? [];
    $p1_array = $_POST['parcial1'] ?? [];
    $p2_array = $_POST['parcial2'] ?? [];
    $p3_array = $_POST['parcial3'] ?? [];

    $error_validacion = false;

    foreach ($inscripciones_ids as $ins_id) {
        $val_p1 = validarParcial($p1_array[$ins_id] ?? '');
        $val_p2 = validarParcial($p2_array[$ins_id] ?? '');
        $val_p3 = validarParcial($p3_array[$ins_id] ?? '');

        if ($val_p1 === false || $val_p2 === false || $val_p3 === false) {
            $error_validacion = true;
            break;
        }

        // Cálculo de la calificación final (promedio institucional de parciales válidos)
        $suma = 0;
        $count = 0;
        foreach ([$val_p1, $val_p2, $val_p3] as $p) {
            if ($p !== null && $p !== 'N/A') {
                $suma += (int)$p;
                $count++;
            }
        }
        $calificacion_final = ($count > 0) ? ($suma / $count) : 0;
        
        // Determinar estatus de la inscripción según la final (aprobado >= 70, reprobado < 70 si ya hay calificaciones completas)
        $estatus_inscripcion = 'cursando';
        if ($count == 3) {
            $estatus_inscripcion = ($calificacion_final >= 70) ? 'aprobado' : 'reprobado';
        }

        // Insertar o actualizar calificaciones usando ON DUPLICATE KEY UPDATE
        $stmt_calif = $pdo->prepare("
            INSERT INTO calificaciones (inscripcion, parcial1, parcial2, parcial3, calificacion_final)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                parcial1 = VALUES(parcial1),
                parcial2 = VALUES(parcial2),
                parcial3 = VALUES(parcial3),
                calificacion_final = VALUES(calificacion_final)
        ");
        $stmt_calif->execute([$ins_id, $val_p1, $val_p2, $val_p3, $calificacion_final]);

        // Actualizar estatus en inscripciones
        $stmt_insc = $pdo->prepare("UPDATE inscripciones SET estatus = ? WHERE id = ?");
        $stmt_insc->execute([$estatus_inscripcion, $ins_id]);
    }

    if ($error_validacion) {
        $mensaje_error = "Error de validación: Las calificaciones parciales deben ser números enteros entre 70 y 100, o la clave 'N/A'.";
    } else {
        $mensaje_exito = "Las calificaciones se han guardado y actualizado correctamente.";
    }
}

// 3. Consultar alumnos y sus calificaciones actuales (HU-14, HU-17)
$alumnos = [];
if ($asignacion_seleccionada) {
    $query_alumnos = $pdo->prepare("
        SELECT 
            i.id AS inscripcion_id,
            a.nombre,
            c.parcial1,
            c.parcial2,
            c.parcial3,
            c.calificacion_final
        FROM inscripciones i
        JOIN alumnos a ON i.alumno = a.id
        LEFT JOIN calificaciones c ON c.inscripcion = i.id
        WHERE i.asignacion = ? AND i.estatus IN ('cursando', 'aprobado', 'reprobado')
        ORDER BY a.nombre ASC
    ");
    $query_alumnos->execute([$asignacion_seleccionada]);
    $alumnos = $query_alumnos->fetchAll();
}

// Cargar la vista HTML separada
include 'calificaciones.html';
?>