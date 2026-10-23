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
$correoSesion = (string)$_SESSION['usuario'];

try {

    // Buscar al empleado que inició sesión
    $stmt = $pdo->prepare(
        'SELECT id, nombre, correo_institucional, telefono, estado
         FROM empleados
         WHERE correo_institucional = ?
         AND estado = 1
         LIMIT 1'
    );

    $stmt->execute([$correoSesion]);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si ya no existe o está inactivo, cerrar sesión
    if (!$empleado) {
        session_unset();
        session_destroy();

        header('Location: ../HTML/login.html');
        exit;
    }

    // Actualizar perfil
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $nombreNuevo = trim($_POST['nombre'] ?? '');
        $correoNuevo = trim($_POST['correo_institucional'] ?? '');
        $telefonoNuevo = trim($_POST['telefono'] ?? '');

        if ($nombreNuevo === '' || $correoNuevo === '') {

            $mensaje = 'El nombre y el correo institucional son obligatorios.';
            $error = true;

        } elseif (!filter_var($correoNuevo, FILTER_VALIDATE_EMAIL)) {

            $mensaje = 'El correo institucional no tiene un formato válido.';
            $error = true;

        } elseif (
            mb_strlen($nombreNuevo) > 100 ||
            mb_strlen($correoNuevo) > 150 ||
            mb_strlen($telefonoNuevo) > 20
        ) {

            $mensaje = 'Uno de los datos supera la longitud permitida.';
            $error = true;

        } else {

            // Verificar que el nuevo correo no pertenezca a otro empleado
            $duplicado = $pdo->prepare(
                'SELECT id
                 FROM empleados
                 WHERE correo_institucional = ?
                 AND id <> ?
                 LIMIT 1'
            );

            $duplicado->execute([
                $correoNuevo,
                (int)$empleado['id']
            ]);

            if ($duplicado->fetch()) {

                $mensaje = 'Ese correo institucional ya está registrado.';
                $error = true;

            } else {

                $actualizar = $pdo->prepare(
                    'UPDATE empleados
                     SET nombre = ?,
                         correo_institucional = ?,
                         telefono = ?
                     WHERE id = ?
                     AND estado = 1'
                );

                $actualizar->execute([
                    $nombreNuevo,
                    $correoNuevo,
                    $telefonoNuevo !== '' ? $telefonoNuevo : null,
                    (int)$empleado['id']
                ]);

                // Actualizar también los datos guardados en sesión
                $_SESSION['usuario'] = $correoNuevo;
                $_SESSION['nombre'] = $nombreNuevo;

                $correoSesion = $correoNuevo;

                $mensaje = 'Perfil actualizado correctamente.';

                // Volver a obtener los datos actualizados
                $stmt->execute([$correoSesion]);
                $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    }

} catch (PDOException $e) {

    $mensaje = 'No fue posible cargar o actualizar el perfil.';
    $error = true;

    $empleado = $empleado ?? [
        'id' => '',
        'nombre' => $_SESSION['nombre'] ?? '',
        'correo_institucional' => $correoSesion,
        'telefono' => '',
        'estado' => 1
    ];
}

$nombre = $_SESSION['nombre'] ?? ($empleado['nombre'] ?? 'Administrador');
$rol = $_SESSION['rol'];

include __DIR__ . '/../HTML/perfilAdmin.html';