<?php

return [
    'secret' => env('JWT_SECRET'),
    'secret_file' => env('JWT_SECRET_FILE'),
    'algorithm' => 'HS256',
    // O token da API permanece válido por 7 dias. O prazo é renovado em um
    // novo login; tokens já emitidos continuam com o prazo original.
    'ttl' => (int) env('JWT_TTL_MINUTES', 10080),
    'issuer' => env('JWT_ISSUER', 'emissor-nfe'),
];
