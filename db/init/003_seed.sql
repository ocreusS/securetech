-- Cliente de ejemplo
INSERT INTO clientes (codigo, nombre, email)
VALUES ('clientepyme1', 'Cliente Pyme 1', 'cliente1@example.com')
ON CONFLICT (codigo) DO NOTHING;

-- Dispositivo Ubuntu del cliente
INSERT INTO dispositivos (cliente_id, device_uid, hostname, sistema_op, ruta_datos, activo)
SELECT c.id, 'clientepyme1-ubuntu', 'clientepyme1-ubuntu', 'Ubuntu 24.04', '/datos_pyme', TRUE
FROM clientes c WHERE c.codigo = 'clientepyme1'
ON CONFLICT (device_uid) DO NOTHING;

-- Dispositivo Windows del cliente
INSERT INTO dispositivos (cliente_id, device_uid, hostname, sistema_op, ruta_datos, activo)
SELECT c.id, 'clientepyme1-windows', 'clientepyme1-windows', 'Windows 10/11', 'C:\DatosPyme', TRUE
FROM clientes c WHERE c.codigo = 'clientepyme1'
ON CONFLICT (device_uid) DO NOTHING;

-- Usuario admin (contraseña: admin1234)
INSERT INTO usuarios_panel (cliente_id, username, password_hash, rol, activo)
VALUES (NULL, 'admin', '$2y$10$yGA1AVMOEgiQNaTvmg1RweAnbC94zZhqnV2VzHmMkvRNdIsHfJ6A6', 'admin', TRUE)
ON CONFLICT (username) DO NOTHING;

-- Usuario cliente (contraseña: cliente1234)
INSERT INTO usuarios_panel (cliente_id, username, password_hash, rol, activo)
SELECT c.id, 'cliente1', '$2y$10$TSn/YsXAKLWiLeU8QjwxxO5nA2VwcDkJSHZBHTPJ82Lht7wVdVy5O', 'cliente', TRUE
FROM clientes c WHERE c.codigo = 'clientepyme1'
ON CONFLICT (username) DO NOTHING;
