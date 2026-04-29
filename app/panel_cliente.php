<?php
require_once __DIR__ . '/lib/auth.php';
requiere_rol(['cliente']);

$user = usuario_actual();
$msg  = htmlspecialchars($_GET['msg'] ?? '');

// Dispositivos del cliente logueado
$stmt = db()->prepare("
    SELECT id, device_uid, hostname, sistema_op
    FROM dispositivos
    WHERE cliente_id = :cid AND activo = TRUE
    ORDER BY id
");
$stmt->execute(['cid' => $user['cliente_id']]);
$dispositivos = $stmt->fetchAll();

// Últimas ejecuciones de este cliente
$stmt = db()->prepare("
    SELECT d.device_uid, e.tipo_copia, e.estado, e.fecha_inicio, e.snapshot_id, e.detalle_error
    FROM ejecuciones_backup e
    JOIN dispositivos d ON d.id = e.dispositivo_id
    WHERE e.cliente_id = :cid
    ORDER BY e.fecha_inicio DESC
    LIMIT 20
");
$stmt->execute(['cid' => $user['cliente_id']]);
$ejecuciones = $stmt->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SecureTECH — Cliente</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; background: #f0f2f5; }
        header { background: #1a1a2e; color: #fff; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
        main { padding: 24px; }
        .form-backup { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.1); margin-bottom: 24px; max-width: 500px; }
        .form-backup label { display: block; margin-top: 12px; font-weight: bold; }
        .form-backup select { width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ccc; border-radius: 4px; }
        .form-backup button { margin-top: 16px; padding: 10px 24px; background: #1a1a2e; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        th { background: #1a1a2e; color: #fff; padding: 10px 14px; text-align: left; }
        td { padding: 10px 14px; border-bottom: 1px solid #eee; font-size: .9rem; }
        .ok { color: #27ae60; font-weight: bold; }
        .error { color: #c0392b; font-weight: bold; }
        .aviso { background: #d5f5e3; border: 1px solid #27ae60; padding: 10px 16px; border-radius: 4px; margin-bottom: 16px; }
        a.salir { color: #fff; text-decoration: none; background: #c0392b; padding: 6px 14px; border-radius: 4px; }
    </style>
</head>
<body>
<header>
    <span>SecureTECH — Panel Cliente</span>
    <a class="salir" href="/logout.php">Cerrar sesión</a>
</header>
<main>
    <?php if ($msg): ?>
    <div class="aviso"><?= $msg ?></div>
    <?php endif; ?>

    <div class="form-backup">
        <h2 style="margin-top:0">Solicitar copia de seguridad</h2>
        <form method="post" action="/api/request_backup.php">
            <label>Dispositivo</label>
            <select name="dispositivo_id" required>
                <?php foreach ($dispositivos as $d): ?>
                <option value="<?= (int)$d['id'] ?>">
                    <?= htmlspecialchars($d['device_uid']) ?> (<?= htmlspecialchars($d['hostname']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <label>Tipo de copia</label>
            <select name="tipo_copia" required>
                <option value="full">Completa (full)</option>
                <option value="incremental" selected>Incremental</option>
            </select>
            <button type="submit">Solicitar copia</button>
        </form>
    </div>

    <h2>Mis copias recientes</h2>
    <table>
        <tr><th>Dispositivo</th><th>Tipo</th><th>Estado</th><th>Fecha</th><th>Snapshot</th></tr>
        <?php foreach ($ejecuciones as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['device_uid']) ?></td>
            <td><?= htmlspecialchars($r['tipo_copia']) ?></td>
            <td class="<?= $r['estado'] === 'OK' ? 'ok' : 'error' ?>"><?= htmlspecialchars($r['estado']) ?></td>
            <td><?= htmlspecialchars($r['fecha_inicio']) ?></td>
            <td style="font-family:monospace;font-size:.8rem"><?= htmlspecialchars(substr((string)$r['snapshot_id'], 0, 12)) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($ejecuciones)): ?>
        <tr><td colspan="5" style="text-align:center;color:#999">Aún no hay copias registradas</td></tr>
        <?php endif; ?>
    </table>
</main>
</body>
</html>
