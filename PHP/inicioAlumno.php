<?php
require_once __DIR__.'/alumno_common.php';
$id=(int)$alumnoActual['id'];
$stmt=$pdo->prepare("SELECT COUNT(*) FROM inscripciones WHERE alumno=?");$stmt->execute([$id]);$totalMaterias=(int)$stmt->fetchColumn();
$stmt=$pdo->prepare("SELECT AVG(c.calificacion_final) FROM calificaciones c JOIN inscripciones i ON i.id=c.inscripcion WHERE i.alumno=? AND i.estatus='aprobado'");$stmt->execute([$id]);$prom=$stmt->fetchColumn();$promedio=$prom===null?'—':number_format((float)$prom,1);
$stmt=$pdo->prepare("SELECT COALESCE(SUM(v.saldo),0) FROM vw_saldo_adeudos v JOIN adeudos a ON a.id=v.adeudo WHERE a.alumno=? AND a.estatus NOT IN ('pagado','cancelado')");$stmt->execute([$id]);$adeudos=(float)$stmt->fetchColumn();
$stmt=$pdo->prepare("SELECT m.nombre,m.clave,mp.grupo,g.nombre AS grupo_nombre,i.estatus,COALESCE(c.calificacion_final,0) AS calificacion_final FROM inscripciones i JOIN materia_profesor mp ON mp.id=i.asignacion JOIN materias m ON m.id=mp.materia JOIN grupos g ON g.id=mp.grupo LEFT JOIN calificaciones c ON c.inscripcion=i.id WHERE i.alumno=? ORDER BY m.nombre LIMIT 5");$stmt->execute([$id]);$materiasRecientes=$stmt->fetchAll();
$stmt=$pdo->prepare("SELECT COUNT(*) FROM asistencias s JOIN inscripciones i ON i.id=s.inscripcion WHERE i.alumno=? AND s.estatus='falta'");$stmt->execute([$id]);$faltas=(int)$stmt->fetchColumn();
alumno_layout('Inicio','Resumen general y estatus académico','inicioAlumno.html',compact('totalMaterias','promedio','adeudos','materiasRecientes','faltas'));
