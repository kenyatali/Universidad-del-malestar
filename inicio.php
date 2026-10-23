
<?php
session_start();
require_once 'conexion.php';

// 1. Obtener el ID del profesor
// Temporalmente se utiliza el ID 1 si todavía no existe una sesión.
// Cuando tu compañera termine login.php, se debe exigir la sesión real.
$id_profesor = $_SESSION['id_profesor'] ?? 1;

// 2. Obtener la información del profesor
$stmt_prof = $pdo->prepare("
    SELECT *
    FROM profesores
    WHERE id = ? AND estado = 1
");

$stmt_prof->execute([$id_profesor]);
$profesor = $stmt_prof->fetch(PDO::FETCH_ASSOC);

// Si el profesor no existe o está inactivo
if (!$profesor) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// 3. Obtener las materias y grupos asignados al profesor
$query_asig = $pdo->prepare("
    SELECT
        mp.id AS asignacion_id,
        m.nombre AS materia,
        m.clave,
        g.nombre AS grupo,
        g.cupo_maximo
    FROM materia_profesor mp
    JOIN materias m ON mp.materia = m.id
    JOIN grupos g ON mp.grupo = g.id
    WHERE mp.profesor = ?
      AND mp.estado = 1
");

$query_asig->execute([$id_profesor]);
$asignaciones = $query_asig->fetchAll(PDO::FETCH_ASSOC);

// 4. Obtener el horario de clases del profesor
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
    WHERE mp.profesor = ?
      AND mp.estado = 1
");

$query_horarios->execute([$id_profesor]);
$horarios = $query_horarios->fetchAll(PDO::FETCH_ASSOC);

// 5. Ordenar los horarios por día de la semana
$orden_dias = [
    'lunes' => 1,
    'martes' => 2,
    'miercoles' => 3,
    'miércoles' => 3,
    'jueves' => 4,
    'viernes' => 5,
    'sabado' => 6,
    'sábado' => 6,
    'domingo' => 7
];

usort($horarios, function ($a, $b) use ($orden_dias) {
    $dia_a = strtolower($a['dia'] ?? '');
    $dia_b = strtolower($b['dia'] ?? '');

    $orden_a = $orden_dias[$dia_a] ?? 99;
    $orden_b = $orden_dias[$dia_b] ?? 99;

    if ($orden_a === $orden_b) {
        return strcmp(
            $a['hora_inicio'] ?? '',
            $b['hora_inicio'] ?? ''
        );
    }

    return $orden_a <=> $orden_b;
});

// 6. Cargar la vista del inicio
include 'inicio.html';
?>
