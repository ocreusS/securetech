<?php
// Configuración central de la aplicación
// Los valores vienen de las variables de entorno del .env
return [
    'db_host'       => 'postgres',
    'db_name'       => getenv('APP_DB_NAME')     ?: 'securetech',
    'db_user'       => getenv('APP_DB_USER')     ?: 'securetech_app',
    'db_pass'       => getenv('APP_DB_PASSWORD') ?: '',
    'api_key'       => getenv('API_SHARED_KEY')  ?: '',
];
