<?php

return [
    'default' => env('DB_CONNECTION', 'pgsql'),
    'connections' => [
        'pgsql' => [
            // Não use DATABASE_URL opcional: uma URL vazia pode sobrescrever
            // silenciosamente a senha definida em DB_PASSWORD.
            'driver' => 'pgsql', 'url' => null,
            // No container, 127.0.0.1 aponta para o próprio app, não para o
            // PostgreSQL. O serviço Docker se chama "db".
            'host' => env('APP_ENV') === 'production'
                ? 'db'
                : env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'nfe'), 'username' => env('DB_USERNAME', 'nfe'),
            // Use o ambiente do processo para não perder a senha quando o
            // carregamento do .env for imutável dentro do container.
            'password' => getenv('DB_PASSWORD') ?: env('DB_PASSWORD', ''),
            'charset' => 'utf8', 'prefix' => '',
            'prefix_indexes' => true, 'search_path' => 'public', 'sslmode' => 'prefer',
        ],
    ],
    'migrations' => 'migrations',
];
