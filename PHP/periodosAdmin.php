<?php
session_start();
require_once __DIR__ . '/conexion.php';
if (!isset($_SESSION['usuario'], $_SESSION['rol']) || $_SESSION['rol'] !== 'administrativo') { header('Location: ../HTML/login.html'); exit(); }
$nombre=$_SESSION['nombre'] ?? 'Administrador'; $rol=$_SESSION['rol']; $mensaje=''; $error=false;
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function postInt($k){ return filter_input(INPUT_POST,$k,FILTER_VALIDATE_INT) ?: 0; }
function auditUser(PDO $pdo){ $s=$pdo->prepare('SET @usuario_app = ?'); $s->execute([$_SESSION['usuario']]); }
function friendly(PDOException $e){ $m=$e->getMessage(); if(stripos($m,'Duplicate')!==false) return 'Ya existe un registro con alguno de los datos únicos proporcionados.'; if(stripos($m,'materias requisito')!==false) return 'El alumno no cumple con los prerrequisitos necesarios para esta materia.'; return 'No fue posible completar la operación. Verifica los datos y sus relaciones.'; }
try{if($_SERVER['REQUEST_METHOD']==='POST'){ $id=postInt('id');$n=trim($_POST['nombre']??'');$i=$_POST['inicio']??'';$f=$_POST['fin']??'';if(!$n||!$i||!$f||$f<=$i)throw new Exception('La fecha fin debe ser posterior a la fecha de inicio.');$s=$id?$pdo->prepare('UPDATE periodos_escolares SET nombre=?,fecha_inicio=?,fecha_fin=? WHERE id=?'):$pdo->prepare('INSERT INTO periodos_escolares(nombre,fecha_inicio,fecha_fin) VALUES(?,?,?)');$s->execute($id?[$n,$i,$f,$id]:[$n,$i,$f]);$mensaje='Periodo guardado.';}}catch(PDOException $e){$error=true;$mensaje=friendly($e);}catch(Exception $e){$error=true;$mensaje=$e->getMessage();}$periodos=$pdo->query('SELECT * FROM periodos_escolares ORDER BY fecha_inicio DESC')->fetchAll();
include __DIR__ . '/../HTML/periodosAdmin.html';
