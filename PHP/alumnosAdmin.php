<?php
session_start();
require_once __DIR__ . '/conexion.php';
if (!isset($_SESSION['usuario'], $_SESSION['rol']) || $_SESSION['rol'] !== 'administrativo') { header('Location: ../HTML/login.html'); exit(); }
$nombre=$_SESSION['nombre'] ?? 'Administrador'; $rol=$_SESSION['rol']; $mensaje=''; $error=false;
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function postInt($k){ return filter_input(INPUT_POST,$k,FILTER_VALIDATE_INT) ?: 0; }
function auditUser(PDO $pdo){ $s=$pdo->prepare('SET @usuario_app = ?'); $s->execute([$_SESSION['usuario']]); }
function friendly(PDOException $e){ $m=$e->getMessage(); if(stripos($m,'Duplicate')!==false) return 'Ya existe un registro con alguno de los datos únicos proporcionados.'; if(stripos($m,'materias requisito')!==false) return 'El alumno no cumple con los prerrequisitos necesarios para esta materia.'; return 'No fue posible completar la operación. Verifica los datos y sus relaciones.'; }
try {
if($_SERVER['REQUEST_METHOD']==='POST'){
 $a=$_POST['accion']??'';
 if($a==='registrar'){ $nom=trim($_POST['nombre']??'');$car=postInt('id_carrera');$cred=(int)($_POST['credito']??0);$cor=trim($_POST['correo']??'');$pass=$_POST['password']??''; if(!$nom||!$car||!filter_var($cor,FILTER_VALIDATE_EMAIL)||$cred<0||strlen($pass)<6) throw new Exception('Datos inválidos. La contraseña debe tener al menos 6 caracteres.'); $s=$pdo->prepare('INSERT INTO alumnos(nombre,id_carrera,credito,correo_institucional,`contraseña`,estado) VALUES(?,?,?,?,?,1)');$s->execute([$nom,$car,$cred,$cor,password_hash($pass,PASSWORD_DEFAULT)]);$mensaje='Alumno registrado correctamente.'; }
 if($a==='actualizar'){ $id=postInt('id');$nom=trim($_POST['nombre']??'');$car=postInt('id_carrera');$cred=(int)($_POST['credito']??0);$cor=trim($_POST['correo']??''); if(!$id||!$nom||!$car||!filter_var($cor,FILTER_VALIDATE_EMAIL)||$cred<0) throw new Exception('Datos inválidos.');$s=$pdo->prepare('UPDATE alumnos SET nombre=?,id_carrera=?,credito=?,correo_institucional=? WHERE id=?');$s->execute([$nom,$car,$cred,$cor,$id]);$mensaje='Alumno actualizado.'; }
 if($a==='estado'){ $id=postInt('id');$estado=(int)($_POST['estado']??0);$s=$pdo->prepare('UPDATE alumnos SET estado=? WHERE id=?');$s->execute([$estado?1:0,$id]);$mensaje='Estado actualizado.'; }
 if ($a === 'cambiar_password') {
    $id = postInt('id');
    $nuevaPassword = $_POST['nueva_password'] ?? '';
    $confirmarPassword = $_POST['confirmar_password'] ?? '';

    if (!$id) {
        throw new Exception('Alumno inválido.');
    }

    if (strlen($nuevaPassword) < 6) {
        throw new Exception('La nueva contraseña debe tener al menos 6 caracteres.');
    }

    if ($nuevaPassword !== $confirmarPassword) {
        throw new Exception('Las contraseñas no coinciden.');
    }

    $s = $pdo->prepare(
        'UPDATE alumnos
         SET `contraseña` = ?
         WHERE id = ?'
    );

    $s->execute([
        password_hash($nuevaPassword, PASSWORD_DEFAULT),
        $id
    ]);

    $mensaje = 'Contraseña del alumno actualizada correctamente.';
}
}
} catch(PDOException $e){$error=true;$mensaje=friendly($e);} catch(Exception $e){$error=true;$mensaje=$e->getMessage();}
$carreras=$pdo->query('SELECT id,nombre FROM carreras ORDER BY nombre')->fetchAll();$q=trim($_GET['q']??'');$s=$pdo->prepare('SELECT a.*,c.nombre carrera FROM alumnos a JOIN carreras c ON c.id=a.id_carrera WHERE a.nombre LIKE ? OR a.correo_institucional LIKE ? ORDER BY a.nombre');$s->execute(["%$q%","%$q%"]);$alumnos=$s->fetchAll();
include __DIR__ . '/../HTML/alumnosAdmin.html';
