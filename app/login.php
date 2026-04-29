<?php
require_once __DIR__ . '/lib/auth.php';

// Si ya hay sesión activa, redirigir al panel correspondiente
if (usuario_actual()) {
    $u = usuario_actual();
    header('Location: ' . ($u['rol'] === 'cliente' ? '/panel_cliente.php' : '/panel_admin.php'));
    exit;
}

$error = '';

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare("
        SELECT id, cliente_id, username, password_hash, rol, activo
        FROM usuarios_panel
        WHERE username = :u
        LIMIT 1
    ");
    $stmt->execute(['u' => $username]);
    $user = $stmt->fetch();

    if (!$user || !$user['activo'] || !password_verify($password, $user['password_hash'])) {
        $error = 'Usuario o contraseña incorrectos';
    } else {
        // Guardar el usuario en la sesión
        $_SESSION['user'] = [
            'id'         => $user['id'],
            'cliente_id' => $user['cliente_id'],
            'username'   => $user['username'],
            'rol'        => $user['rol'],
        ];
        header('Location: ' . ($user['rol'] === 'cliente' ? '/panel_cliente.php' : '/panel_admin.php'));
        exit;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SecureTECH — Acceso</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; margin: 0; }
        .caja {
            max-width: 400px; margin: 100px auto;
            background: #fff; padding: 32px; border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.12);
        }
        h1 { margin-top: 0; color: #1a1a2e; }
        label { display: block; margin-top: 16px; font-weight: bold; }
        input[type=text], input[type=password] {
            width: 100%; padding: 10px; margin-top: 4px;
            border: 1px solid #ccc; border-radius: 4px; font-size: 1rem;
        }
        button {
            width: 100%; margin-top: 20px; padding: 12px;
            background: #1a1a2e; color: #fff;
            border: none; border-radius: 4px; font-size: 1rem; cursor: pointer;
        }
        button:hover { background: #16213e; }
        .error { color: #c0392b; margin-top: 12px; }
    </style>
</head>
<body>
<div class="caja">
    <h1>SecureTECH</h1>
    <p>Sistema de copias de seguridad</p>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post">
        <label>Usuario</label>
        <input type="text" name="username" required autofocus>
        <label>Contraseña</label>
        <input type="password" name="password" required>
        <button type="submit">Entrar</button>
    </form>
</div>
</body>
</html>
