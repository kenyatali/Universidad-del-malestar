<?php
session_start();
require_once 'conexion.php';

// Si es una petición POST (formulario enviado), limpiamos cualquier sesión previa para evaluar las nuevas credenciales de forma estricta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Limpiar sesión previa
    unset($_SESSION['usuario']);
    unset($_SESSION['nombre']);
    unset($_SESSION['rol']);

    if (!empty($correo) && !empty($password)) {
        $usuarioEncontrado = null;
        $rolEncontrado = '';
        $nombreEncontrado = '';

        // 1. Buscar en alumnos
        $stmt = $pdo->prepare("SELECT * FROM alumnos WHERE correo_institucional = ? AND estado = 1");
        $stmt->execute([$correo]);
        $alumno = $stmt->fetch();

        if ($alumno) {
            $passBD = $alumno['contraseña'] ?? $alumno['password'] ?? '';
            if ($password === $passBD || password_verify($password, $passBD)) {
                $usuarioEncontrado = $alumno['correo_institucional'];
                $nombreEncontrado = $alumno['nombre'];
                $rolEncontrado = 'alumno';
            }
        }

        // 2. Buscar en profesores
        if (!$usuarioEncontrado) {
            $stmt = $pdo->prepare("SELECT * FROM profesores WHERE correo_institucional = ? AND estado = 1");
            $stmt->execute([$correo]);
            $profesor = $stmt->fetch();

            if ($profesor) {
                $passBD = $profesor['contraseña'] ?? $profesor['password'] ?? '';
                if ($password === $passBD || password_verify($password, $passBD)) {
                    $usuarioEncontrado = $profesor['correo_institucional'];
                    $nombreEncontrado = $profesor['nombre'];
                    $rolEncontrado = 'docente';
                }
            }
        }

        // 3. Buscar en empleados
        if (!$usuarioEncontrado) {
            $stmt = $pdo->prepare("SELECT * FROM empleados WHERE correo_institucional = ? AND estado = 1");
            $stmt->execute([$correo]);
            $empleado = $stmt->fetch();

            if ($empleado) {
                $passBD = $empleado['contraseña'] ?? $empleado['password'] ?? '';
                if ($password === $passBD || password_verify($password, $passBD)) {
                    $usuarioEncontrado = $empleado['correo_institucional'];
                    $nombreEncontrado = $empleado['nombre'];
                    $rolEncontrado = 'administrativo';
                }
            }
        }

        // Evaluación del resultado
        if ($usuarioEncontrado) {
            $_SESSION['usuario'] = $usuarioEncontrado;
            $_SESSION['nombre'] = $nombreEncontrado;
            $_SESSION['rol'] = $rolEncontrado;

            $pdo->exec("SET @usuario_app = " . $pdo->quote($usuarioEncontrado));

            if ($rolEncontrado === 'alumno') {
                header('Location: inicioAlumno.php');
            } elseif ($rolEncontrado === 'docente') {
                header('Location: inicioDocente.php');
            } elseif ($rolEncontrado === 'administrativo') {
                header('Location: inicioAdmin.php');
            }
            exit();
        } else {
            $_SESSION['error_login'] = 'Correo institucional o contraseña incorrectos.';
            header('Location: ../HTML/login.html');
            exit();
        }
    } else {
        $_SESSION['error_login'] = 'Por favor, completa todos los campos.';
        header('Location: ../HTML/login.html');
        exit();
    }
} else {
    // Si la petición es GET y ya hay sesión iniciada, redirige a su respectivo inicio
    if (isset($_SESSION['usuario'])) {
        if ($_SESSION['rol'] === 'alumno') {
            header('Location: inicioAlumno.php');
        } elseif ($_SESSION['rol'] === 'docente') {
            header('Location: inicioDocente.php');
        } elseif ($_SESSION['rol'] === 'administrativo') {
            header('Location: inicioAdmin.php');
        }
        exit();
    }
    
    header('Location: ../HTML/login.html');
    exit();
}