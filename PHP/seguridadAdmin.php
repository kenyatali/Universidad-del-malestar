<?php
session_start();
require_once __DIR__ . '/conexion.php';

if (
    !isset($_SESSION['usuario'], $_SESSION['rol']) ||
    $_SESSION['rol'] !== 'administrativo'
) {
    header('Location: ../HTML/login.html');
    exit;
}

function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$mensaje = '';
$error = false;

$correo = (string)$_SESSION['usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $actual = (string)($_POST['password_actual'] ?? '');
    $nueva = (string)($_POST['password_nueva'] ?? '');
    $confirmar = (string)($_POST['password_confirmar'] ?? '');

    if ($actual === '' || $nueva === '' || $confirmar === '') {

        $mensaje = 'Completa todos los campos.';
        $error = true;

    } elseif (strlen($nueva) < 8) {

        $mensaje = 'La nueva contraseña debe tener al menos 8 caracteres.';
        $error = true;

    } elseif ($nueva !== $confirmar) {

        $mensaje = 'La nueva contraseña y su confirmación no coinciden.';
        $error = true;

    } elseif ($actual === $nueva) {

        $mensaje = 'La nueva contraseña debe ser diferente de la actual.';
        $error = true;

    } else {

        try {

            // Obtener contraseña actual del empleado
            $stmt = $pdo->prepare(
                'SELECT id, `contraseña`
                 FROM empleados
                 WHERE correo_institucional = ?
                 AND estado = 1
                 LIMIT 1'
            );

            $stmt->execute([$correo]);

            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                !$empleado ||
                !password_verify(
                    $actual,
                    (string)$empleado['contraseña']
                )
            ) {

                $mensaje = 'La contraseña actual es incorrecta.';
                $error = true;

            } else {

                // Generar el nuevo hash
                $hash = password_hash(
                    $nueva,
                    PASSWORD_DEFAULT
                );

                // Actualizar contraseña
                $actualizar = $pdo->prepare(
                    'UPDATE empleados
                     SET `contraseña` = ?
                     WHERE id = ?'
                );

                $actualizar->execute([
                    $hash,
                    (int)$empleado['id']
                ]);

                $mensaje = 'Contraseña actualizada correctamente.';
            }

        } catch (PDOException $e) {

            $mensaje = 'No fue posible actualizar la contraseña.';
            $error = true;
        }
    }
}

$nombre = $_SESSION['nombre'] ?? 'Administrador';
$rol = $_SESSION['rol'];

include __DIR__ . '/../HTML/seguridadAdmin.html';