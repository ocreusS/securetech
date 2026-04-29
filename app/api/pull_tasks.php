<?php
// El agente del cliente llama a este endpoint cada 5 minutos.
// Si hay una solicitud pending para ese dispositivo, la devuelve y la marca como "taken".
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

$cfg    = require __DIR__ . '/../config.php';
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$devId  = $_GET['device_id'] ?? '';

// Validar la clave de API
if (!hash_equals($cfg['api_key'], $apiKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'API key inválida']);
    exit;
}

$db = db();
$db->beginTransaction();

try {
    // Buscar la solicitud más antigua pendiente para este dispositivo
    $stmt = $db->prepare("
        SELECT s.id, s.tipo_copia
        FROM solicitudes_backup s
        JOIN dispositivos d ON d.id = s.dispositivo_id
        WHERE d.device_uid = :dev AND s.estado = 'pending'
        ORDER BY s.creada_en ASC
        LIMIT 1
    ");
    $stmt->execute(['dev' => $devId]);
    $task = $stmt->fetch();

    if (!$task) {
        $db->commit();
        echo json_encode(['task' => null]);
        exit;
    }

    // Marcarla como "taken" (el agente la ha recogido)
    $db->prepare("UPDATE solicitudes_backup SET estado='taken', tomada_en=NOW() WHERE id=:id")
       ->execute(['id' => $task['id']]);

    $db->commit();
    echo json_encode([
        'task' => [
            'id'         => (int)$task['id'],
            'tipo_copia' => $task['tipo_copia'],
        ]
    ]);

} catch (Throwable $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
