<?php

return [
    'uf' => env('NFE_UF', 'SP'),
    'cuf' => (int) env('NFE_CUF', 35),
    'ambiente' => (int) env('NFE_AMBIENTE', 2),
    'simulate' => filter_var(env('NFE_SIMULATE', false), FILTER_VALIDATE_BOOL),
    'schemes' => env('NFE_SCHEMES', 'PL_010_V1.20'),
    'versao' => env('NFE_VERSAO', '4.00'),
    'razao_social' => env('NFE_RAZAO_SOCIAL'),
    'cnpj' => env('NFE_CNPJ'),
    'ie' => env('NFE_IE'),
    'crt' => (int) env('NFE_CRT', 1),
    'certificate_path' => env('NFE_CERTIFICATE_PATH', '/run/secrets/nfe/emissor.pfx'),
    'certificate_password' => env('NFE_CERTIFICATE_PASSWORD'),
    'certificate_password_file' => env('NFE_CERTIFICATE_PASSWORD_FILE'),
];
