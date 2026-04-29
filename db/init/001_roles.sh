#!/usr/bin/env bash
# Crea los roles (usuarios) de PostgreSQL para la aplicación
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    DO \$\$
    BEGIN
        IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'securetech_app') THEN
            CREATE ROLE securetech_app LOGIN PASSWORD '${APP_DB_PASSWORD}';
        END IF;
        IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'grafana_reader') THEN
            CREATE ROLE grafana_reader LOGIN PASSWORD '${GRAFANA_DB_PASSWORD}';
        END IF;
    END
    \$\$;
EOSQL
