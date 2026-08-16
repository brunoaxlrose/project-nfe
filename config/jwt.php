<?php

return [
    'secret' => env('JWT_SECRET'),
    'secret_file' => env('JWT_SECRET_FILE'),
    'algorithm' => 'HS256',
    'ttl' => (int) env('JWT_TTL_MINUTES', 15),
    'issuer' => env('JWT_ISSUER', 'emissor-nfe'),
];
