<?php
require_once __DIR__ . '/conexion.php';

$ok = false;
$mensaje = '';
$tablas = [];

try {
    $pdo = Conexion::obtener();
    $tablas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $ok = true;
    $mensaje = 'Conexión exitosa a la base de datos.';
} catch (Throwable $e) {
    $mensaje = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba de conexión</title>
    <style>
        body { background:#111B1A; color:#E2ECE9; font-family:sans-serif; display:flex; justify-content:center; padding:40px; }
        .card { background:#1B2826; border:1px solid #2C3E3B; border-radius:12px; padding:24px 32px; min-width:320px; }
        .ok { color:#66BB6A; } .error { color:#E57373; }
        small { color:#92A8A4; }
        li { margin:4px 0; }
    </style>
</head>
<body>
    <div class="card">
        <h2 class="<?= $ok ? 'ok' : 'error' ?>"><?= $ok ? '✔' : '✖' ?> <?= htmlspecialchars($mensaje) ?></h2>
        <?php if ($ok): ?>
            <small>Tablas encontradas: <?= count($tablas) ?> (deberían ser 19: 18 tablas + 1 vista)</small>
            <ul>
                <?php foreach ($tablas as $t): ?>
                    <li><?= htmlspecialchars($t) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <small>Revisa el .env y que MySQL esté corriendo.</small>
        <?php endif; ?>
    </div>
</body>
</html>
