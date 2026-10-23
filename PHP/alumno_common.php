<?php
// Funciones compartidas del portal del alumno. Reutiliza PHP/conexion.php y la sesión del login existente.
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'alumno') {
    header('Location: ../HTML/login.html');
    exit;
}
require_once __DIR__ . '/conexion.php';
$correoUsuario = (string) $_SESSION['usuario'];
$stmtAlumno = $pdo->prepare('SELECT a.id, a.nombre, a.id_carrera, a.credito, a.correo_institucional, a.estado, c.nombre AS carrera_nombre, c.apodo AS carrera_apodo FROM alumnos a LEFT JOIN carreras c ON c.id = a.id_carrera WHERE a.correo_institucional = ? LIMIT 1');
$stmtAlumno->execute([$correoUsuario]);
$alumnoActual = $stmtAlumno->fetch();
if (!$alumnoActual) {
    session_destroy();
    header('Location: ../HTML/login.html');
    exit;
}
$nombre = $alumnoActual['nombre'];
$rol = 'alumno';
function h($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function fecha_es(?string $fecha): string {
    if (!$fecha) return '—';
    $ts = strtotime($fecha);
    return $ts ? date('d/m/Y', $ts) : h($fecha);
}
function dinero($monto): string { return '$' . number_format((float)$monto, 2); }
function estado_badge(string $estado): string {
    $map = ['cursando'=>'info','aprobado'=>'success','reprobado'=>'danger','asistencia'=>'success','falta'=>'danger','retardo'=>'warning','justificada'=>'info','pendiente'=>'warning','parcial'=>'warning','pagado'=>'success','vencido'=>'danger','cancelado'=>'muted'];
    return '<span class="badge '.($map[$estado] ?? 'muted').'">'.h(ucfirst($estado)).'</span>';
}
function alumno_layout(string $titulo, string $subtitulo, string $vista, array $vars = []): void {
    global $alumnoActual, $nombre, $rol, $pdo;
    extract($vars, EXTR_SKIP);
    $tituloPagina = $titulo;
    $subtituloPagina = $subtitulo;
    $vistaContenido = $vista;
    include __DIR__ . '/../HTML/alumnoLayout.php';
}
