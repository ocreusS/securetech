<?php
// Recibe la petición del panel del cliente y crea la solicitud en la BD
require_once __DIR__ . '/../lib/auth.php';
requiere_login();

$user          = usuario_actual();
$dispositivo_id = (int)($_POST['dispositivo_id'] ?? 0);
$tipo_copia    = $_POST['tipo_copia'] ?? 'incremental';

if (!in_array($tipo_copia, ['full', 'incremental'], true)) {
    http_response_code(400);
    exit('Tipo de copia no válido');
}

// Comprobar que el dispositivo existe y pertenece al cliente
$stmt = db()->prepare("SELECT cliente_id FROM dispositivos WHERE id = :id AND activo = TRUE");
$stmt->execute(['id' => $dispositivo_id]);
$dev = $stmt->fetch();

if (!$dev) {
    http_response_code(404);
    exit('Dispositivo no encontrado');
}

// Un cliente solo puede pedir backup de sus propios dispositivos
if ($user['rol'] === 'cliente' && (int)$dev['cliente_id'] !== (int)$user['cliente_id']) {
    http_response_code(403);
    exit('No autorizado');
}

// Insertar la solicitud
$stmt = db()->prepare("
    INSERT INTO solicitudes_backup (cliente_id, dispositivo_id, pedida_por, tipo_copia, estado)
    VALUES (:cid, :did, :uid, :tipo, 'pending')
");
$stmt->execute([
    'cid'  => $dev['cliente_id'],
    'did'  => $dispositivo_id,
    'uid'  => $user['id'],
    'tipo' => $tipo_copia,
]);

header('Location: /panel_cliente.php?msg=Solicitud+creada.+El+agente+la+procesará+en+breve.');
exit;
