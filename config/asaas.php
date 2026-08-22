<?php

return [
    'api_key' => env('ASAAS_API_KEY'),
    'base_url' => env('ASAAS_BASE_URL', 'https://api-sandbox.asaas.com/v3'),
    'renewal_plan_slug' => env('ASAAS_RENEWAL_PLAN_SLUG', 'legado-completo'),
];
