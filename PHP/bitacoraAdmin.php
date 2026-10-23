<?php
session_start();
require_once __DIR__ . '/conexion.php';
if (!isset($_SESSION['usuario'], $_SESSION['rol']) || $_SESSION['rol'] !== 'administrativo') { header('Location: ../HTML/login.html'); exit(); }
$nombre=$_SESSION['nombre'] ?? 'Administrador'; $rol=$_SESSION['rol']; $mensaje=''; $error=false;
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function postInt($k){ return filter_input(INPUT_POST,$k,FILTER_VALIDATE_INT) ?: 0; }
function auditUser(PDO $pdo){ $s=$pdo->prepare('SET @usuario_app = ?'); $s->execute([$_SESSION['usuario']]); }
function friendly(PDOException $e){ $m=$e->getMessage(); if(stripos($m,'Duplicate')!==false) return 'Ya existe un registro con alguno de los datos únicos proporcionados.'; if(stripos($m,'materias requisito')!==false) return 'El alumno no cumple con los prerrequisitos necesarios para esta materia.'; return 'No fue posible completar la operación. Verifica los datos y sus relaciones.'; }
$tabla=trim($_GET['tabla']??'');$accion=trim($_GET['accion']??'');$usuario=trim($_GET['usuario']??'');$desde=$_GET['desde']??'';$hasta=$_GET['hasta']??'';$where=[];$args=[];if($tabla!==''){$where[]='tabla=?';$args[]=$tabla;}if($accion!==''){$where[]='accion=?';$args[]=$accion;}if($usuario!==''){$where[]='usuario_app LIKE ?';$args[]="%$usuario%";}if($desde!==''){$where[]='fecha>=?';$args[]=$desde.' 00:00:00';}if($hasta!==''){$where[]='fecha<=?';$args[]=$hasta.' 23:59:59';}$sql='SELECT * FROM bitacora'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY fecha DESC LIMIT 500';$s=$pdo->prepare($sql);$s->execute($args);$movimientos=$s->fetchAll();
include __DIR__ . '/../HTML/bitacoraAdmin.html';
