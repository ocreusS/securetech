-- Clientes: empresas que contratan el servicio
CREATE TABLE IF NOT EXISTS clientes (
    id          BIGSERIAL PRIMARY KEY,
    codigo      TEXT NOT NULL UNIQUE,   -- identificador corto, ej: "clientepyme1"
    nombre      TEXT NOT NULL,
    email       TEXT NOT NULL,
    creado_en   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Dispositivos: ordenadores de cada cliente
CREATE TABLE IF NOT EXISTS dispositivos (
    id              BIGSERIAL PRIMARY KEY,
    cliente_id      BIGINT NOT NULL REFERENCES clientes(id) ON DELETE CASCADE,
    device_uid      TEXT NOT NULL UNIQUE,  -- identificador único del dispositivo
    hostname        TEXT NOT NULL,
    sistema_op      TEXT NOT NULL,
    ruta_datos      TEXT NOT NULL,
    activo          BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Usuarios del panel web (admin y clientes)
CREATE TABLE IF NOT EXISTS usuarios_panel (
    id              BIGSERIAL PRIMARY KEY,
    cliente_id      BIGINT REFERENCES clientes(id) ON DELETE CASCADE,
    username        TEXT NOT NULL UNIQUE,
    password_hash   TEXT NOT NULL,
    rol             TEXT NOT NULL CHECK (rol IN ('admin', 'cliente')),
    activo          BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Solicitudes de backup: las pide el cliente desde el panel
CREATE TABLE IF NOT EXISTS solicitudes_backup (
    id              BIGSERIAL PRIMARY KEY,
    cliente_id      BIGINT NOT NULL REFERENCES clientes(id) ON DELETE CASCADE,
    dispositivo_id  BIGINT NOT NULL REFERENCES dispositivos(id) ON DELETE CASCADE,
    pedida_por      BIGINT REFERENCES usuarios_panel(id) ON DELETE SET NULL,
    tipo_copia      TEXT NOT NULL CHECK (tipo_copia IN ('full', 'incremental')),
    estado          TEXT NOT NULL DEFAULT 'pending'
                        CHECK (estado IN ('pending', 'taken', 'done', 'error')),
    creada_en       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    tomada_en       TIMESTAMPTZ,
    finalizada_en   TIMESTAMPTZ
);

-- Ejecuciones: registro de cada backup realizado (por cron o por solicitud)
CREATE TABLE IF NOT EXISTS ejecuciones_backup (
    id              BIGSERIAL PRIMARY KEY,
    cliente_id      BIGINT NOT NULL REFERENCES clientes(id) ON DELETE CASCADE,
    dispositivo_id  BIGINT NOT NULL REFERENCES dispositivos(id) ON DELETE CASCADE,
    solicitud_id    BIGINT REFERENCES solicitudes_backup(id) ON DELETE SET NULL,
    snapshot_id     TEXT,
    tipo_copia      TEXT NOT NULL CHECK (tipo_copia IN ('full', 'incremental')),
    estado          TEXT NOT NULL CHECK (estado IN ('OK', 'ERROR')),
    fecha_inicio    TIMESTAMPTZ NOT NULL,
    fecha_fin       TIMESTAMPTZ,
    duracion_s      INTEGER,
    tamano_bytes    BIGINT,
    detalle_error   TEXT,
    creado_en       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Vista útil para Grafana: último estado de cada dispositivo
CREATE OR REPLACE VIEW v_ultimo_backup AS
SELECT DISTINCT ON (d.id)
    d.id            AS dispositivo_id,
    c.codigo        AS cliente,
    d.device_uid,
    d.hostname,
    e.estado,
    e.tipo_copia,
    e.snapshot_id,
    e.fecha_inicio,
    e.tamano_bytes,
    e.detalle_error
FROM dispositivos d
JOIN clientes c ON c.id = d.cliente_id
LEFT JOIN ejecuciones_backup e ON e.dispositivo_id = d.id
ORDER BY d.id, e.fecha_inicio DESC NULLS LAST;

-- Permisos: securetech_app puede leer y escribir
GRANT CONNECT ON DATABASE securetech TO securetech_app, grafana_reader;
GRANT USAGE ON SCHEMA public TO securetech_app, grafana_reader;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO securetech_app;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO securetech_app;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO grafana_reader;

ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO securetech_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT USAGE, SELECT ON SEQUENCES TO securetech_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT SELECT ON TABLES TO grafana_reader;
