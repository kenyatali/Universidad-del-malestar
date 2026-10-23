<?php
function db(): PDO {
 static $pdo=null;
 if ($pdo instanceof PDO) return $pdo;
 $host='127.0.0.1'; $name='gestion_escolar'; $user='root'; $pass='';
 try { $pdo=new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }
 catch (PDOException $e) { http_response_code(500); echo '<!doctype html><meta charset="utf-8"><div style="font:16px Arial;max-width:700px;margin:10vh auto;padding:30px;background:#fff4e5;border-radius:16px"><h2>No se pudo conectar con MySQL</h2><p>Abre XAMPP y enciende Apache y MySQL. Confirma que exista la base <b>gestion_escolar</b> y revisa las credenciales en <code>back/config/database.php</code>.</p><small>Detalle técnico: '.htmlspecialchars($e->getMessage()).'</small></div>'; exit; }
 return $pdo;
}
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
