<?php
// El agente llama a este endpoint al terminar el backup para guardar el resultado.
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

$cfg    = require __DIR__ . '/../config.php';
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

if (!hash_equals($cfg['api_key'], $apiKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'API key inválida']);
    exit;
}

// Leer el JSON que manda el agente
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

// Campos obligatorios
foreach (['device_uid', 'tipo_copia', 'estado', 'fecha_inicio'] as $f) {
    if (empty($data[$f])) {
        http_response_code(400);
        echo json_encode(['error' => "Falta campo: $f"]);
        exit;
    }
}

// Buscar el dispositivo
$stmt = db()->prepare("
    SELECT id AS dispositivo_id, cliente_id
    FROM dispositivos WHERE device_uid = :uid AND activo = TRUE LIMIT 1
");
$stmt->execute(['uid' => $data['device_uid']]);
$dev = $stmt->fetch();

if (!$dev) {
    http_response_code(404);
    echo json_encode(['error' => 'Dispositivo no encontrado']);
    exit;
}

$db = db();
$db->beginTransaction();

try {
    // Guardar la ejecución
    $stmt = $db->prepare("
        INSERT INTO ejecuciones_backup
            (cliente_id, dispositivo_id, solicitud_id, snapshot_id,
             tipo_copia, estado, fecha_inicio, fecha_fin,
             duracion_s, tamano_bytes, detalle_error)
        VALUES
            (:cid, :did, :sid, :snap,
             :tipo, :estado, :fi, :ff,
             :dur, :tam, :err)
        RETURNING id
    ");
    $stmt->execute([
        'cid'    => $dev['cliente_id'],
        'did'    => $dev['dispositivo_id'],
        'sid'    => $data['task_id']        ?? null,
        'snap'   => $data['snapshot_id']    ?? null,
        'tipo'   => $data['tipo_copia'],
        'estado' => $data['estado'],
        'fi'     => $data['fecha_inicio'],
        'ff'     => $data['fecha_fin']      ?? null,
        'dur'    => $data['duracion_s']     ?? null,
        'tam'    => $data['tamano_bytes']   ?? null,
        'err'    => $data['detalle_error']  ?? null,
    ]);
    $ej_id = $stmt->fetchColumn();

    // Si venía de una solicitud, actualizarla a done o error
    if (!empty($data['task_id'])) {
        $nuevo_estado = ($data['estado'] === 'OK') ? 'done' : 'error';
        $db->prepare("
            UPDATE solicitudes_backup
            SET estado = :est, finalizada_en = NOW()
            WHERE id = :id
        ")->execute(['est' => $nuevo_estado, 'id' => $data['task_id']]);
    }

    $db->commit();
    echo json_encode(['ok' => true, 'ejecucion_id' => (int)$ej_id]);

} catch (Throwable $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
