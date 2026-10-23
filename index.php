<?php
session_start();
require_once __DIR__.'/back/config/database.php';
require_once __DIR__.'/back/models/Alumno.php';
require_once __DIR__.'/back/controllers/AlumnoController.php';
$alumnoController = new AlumnoController();
$page = $_GET['page'] ?? 'inicio';
$allowed = ['inicio','perfil','materias','horarios','calificaciones','asistencias','pagos','cuenta'];
if (!in_array($page,$allowed,true)) $page='inicio';
// Módulo de interfaces sin login: usa ?alumno=ID para seleccionar un registro.
$alumnoId = max(1, (int)($_GET['alumno'] ?? 1));
try {
  $data = $alumnoController->data($page, $alumnoId);
  if (empty($data['perfil'])) {
    http_response_code(404);
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Alumno no encontrado</title><body style="background:#111B1A;color:#E2ECE9;font:16px Arial;padding:3rem"><h1>No se encontró el alumno</h1><p>Verifica que exista un registro en la tabla alumnos. Puedes indicar otro ID con <code>?alumno=ID</code>.</p><a style="color:#8DD0C8" href="index.php">Volver al inicio</a></body></html>';
    exit;
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Error de conexión</title><body style="background:#111B1A;color:#E2ECE9;font:16px Arial;padding:3rem"><h1>No se pudo cargar el módulo</h1><p>Revisa que Apache y MySQL estén activos y que exista la base gestion_escolar.</p><p style="color:#92A8A4">Detalle: '.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8').'</p></body></html>';
}
require __DIR__.'/back/views/layout.php';
