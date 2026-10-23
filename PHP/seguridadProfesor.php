<?php
session_start();
require_once __DIR__ . '/conexion.php';

// Validar que el profesor esté autenticado y activo (HU-24)
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

$mensaje_exito = '';
$mensaje_error = '';

// Procesar cambio de contraseña (HU-23)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nuevo = $_POST['password_nuevo'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';

    if (empty($password_actual) || empty($password_nuevo) || empty($password_confirmar)) {
        $mensaje_error = "Todos los campos son obligatorios.";
    } elseif ($password_nuevo !== $password_confirmar) {
        $mensaje_error = "La nueva contraseña y la confirmación no coinciden.";
    } elseif (strlen($password_nuevo) < 6) {
        $mensaje_error = "La nueva contraseña debe tener al menos 6 caracteres.";
    } else {
        // Obtener contraseña actual de la base de datos
        $stmt = $pdo->prepare("SELECT `contraseña` FROM profesores WHERE id = ? AND estado = 1");
        $stmt->execute([$id_profesor]);
        $prof = $stmt->fetch();

        if ($prof && password_verify($password_actual, $prof['contraseña'])) {
            // Cifrar nueva contraseña de forma segura (Criterio de aceptación: sin texto plano)
            $nuevo_hash = password_hash($password_nuevo, PASSWORD_DEFAULT);

            $update = $pdo->prepare("UPDATE profesores SET `contraseña` = ? WHERE id = ?");
            $update->execute([$nuevo_hash, $id_profesor]);

            $mensaje_exito = "Tu contraseña ha sido actualizada correctamente.";
        } else {
            $mensaje_error = "La contraseña actual es incorrecta.";
        }
    }
}

// Cargar la vista HTML separada
include __DIR__ . '/../HTML/seguridadProfesor.html';
?>