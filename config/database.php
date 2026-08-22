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
            // No Docker local, DB_HOST=db. No Render, DB_HOST aponta para o
            // Supabase; não force o hostname do compose em produção.
            'host' => env('DB_HOST', env('APP_ENV') === 'production' ? 'db' : '127.0.0.1'),
            'port' => env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'nfe'), 'username' => env('DB_USERNAME', 'nfe'),
            // Use o ambiente do processo para não perder a senha quando o
            // carregamento do .env for imutável dentro do container.
            'password' => getenv('DB_PASSWORD') ?: env('DB_PASSWORD', ''),
            'charset' => 'utf8', 'prefix' => '',
            'prefix_indexes' => true, 'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],
    ],
    'migrations' => 'migrations',
];
