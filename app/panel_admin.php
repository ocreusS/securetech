<?php
require_once __DIR__ . '/lib/auth.php';
requiere_rol(['admin']);

// Estadísticas generales
$n_clientes    = db()->query('SELECT COUNT(*) FROM clientes')->fetchColumn();
$n_dispositivos = db()->query('SELECT COUNT(*) FROM dispositivos WHERE activo = TRUE')->fetchColumn();
$n_fallos      = db()->query("
    SELECT COUNT(*) FROM ejecuciones_backup
    WHERE estado = 'ERROR' AND fecha_inicio > NOW() - INTERVAL '24 hours'
")->fetchColumn();

// Últimas 20 ejecuciones
$ejecuciones = db()->query("
    SELECT c.nombre AS cliente, d.device_uid, e.tipo_copia, e.estado,
           e.fecha_inicio, e.tamano_bytes, e.detalle_error
    FROM ejecuciones_backup e
    JOIN clientes c    ON c.id = e.cliente_id
    JOIN dispositivos d ON d.id = e.dispositivo_id
    ORDER BY e.fecha_inicio DESC
    LIMIT 20
")->fetchAll();

// Solicitudes pendientes
$solicitudes = db()->query("
    SELECT s.id, c.nombre AS cliente, d.device_uid, s.tipo_copia, s.estado, s.creada_en
    FROM solicitudes_backup s
    JOIN clientes c    ON c.id = s.cliente_id
    JOIN dispositivos d ON d.id = s.dispositivo_id
    WHERE s.estado IN ('pending','taken')
    ORDER BY s.creada_en DESC
")->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SecureTECH — Admin</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; background: #f0f2f5; }
        header { background: #1a1a2e; color: #fff; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
        main { padding: 24px; }
        .tarjetas { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
        .tarjeta { background: #fff; padding: 20px 28px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        .tarjeta strong { display: block; font-size: 2rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.1); margin-bottom: 24px; }
        th { background: #1a1a2e; color: #fff; padding: 10px 14px; text-align: left; }
        td { padding: 10px 14px; border-bottom: 1px solid #eee; font-size: .9rem; }
        .ok { color: #27ae60; font-weight: bold; }
        .error { color: #c0392b; font-weight: bold; }
        a { color: #fff; text-decoration: none; background: #c0392b; padding: 6px 14px; border-radius: 4px; }
        h2 { color: #1a1a2e; }
    </style>
</head>
<body>
<header>
    <span>SecureTECH — Panel Administrador</span>
    <a href="/logout.php">Cerrar sesión</a>
</header>
<main>
    <div class="tarjetas">
        <div class="tarjeta"><strong><?= (int)$n_clientes ?></strong>Clientes</div>
        <div class="tarjeta"><strong><?= (int)$n_dispositivos ?></strong>Dispositivos activos</div>
        <div class="tarjeta"><strong><?= (int)$n_fallos ?></strong>Fallos últimas 24h</div>
    </div>

    <h2>Solicitudes activas</h2>
    <table>
        <tr><th>ID</th><th>Cliente</th><th>Dispositivo</th><th>Tipo</th><th>Estado</th><th>Creada</th></tr>
        <?php foreach ($solicitudes as $r): ?>
        <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['cliente']) ?></td>
            <td><?= htmlspecialchars($r['device_uid']) ?></td>
            <td><?= htmlspecialchars($r['tipo_copia']) ?></td>
            <td><?= htmlspecialchars($r['estado']) ?></td>
            <td><?= htmlspecialchars($r['creada_en']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($solicitudes)): ?>
        <tr><td colspan="6" style="text-align:center;color:#999">Sin solicitudes activas</td></tr>
        <?php endif; ?>
    </table>

    <h2>Últimas ejecuciones</h2>
    <table>
        <tr><th>Cliente</th><th>Dispositivo</th><th>Tipo</th><th>Estado</th><th>Fecha</th><th>Tamaño</th></tr>
        <?php foreach ($ejecuciones as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['cliente']) ?></td>
            <td><?= htmlspecialchars($r['device_uid']) ?></td>
            <td><?= htmlspecialchars($r['tipo_copia']) ?></td>
            <td class="<?= $r['estado'] === 'OK' ? 'ok' : 'error' ?>"><?= htmlspecialchars($r['estado']) ?></td>
            <td><?= htmlspecialchars($r['fecha_inicio']) ?></td>
            <td><?= htmlspecialchars(number_format((int)$r['tamano_bytes'] / 1024, 0)) ?> KB</td>
        </tr>
        <?php endforeach; ?>
    </table>
</main>
</body>
</html>
