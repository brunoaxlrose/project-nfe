<?php

return [
    // O JWT é validado pelo middleware App\Http\Middleware\JwtAuthenticate.
    // O guard padrão continua sendo o guard Laravel convencional para que
    // middlewares como throttle não tentem resolver um driver "request".
    'defaults' => ['guard' => 'web', 'passwords' => 'users'],
    'guards' => [
        'web' => ['driver' => 'session', 'provider' => 'users'],
        'api' => ['driver' => 'session', 'provider' => 'users'],
    ],
    'providers' => ['users' => ['driver' => 'eloquent', 'model' => App\Models\User::class]],
    'passwords' => ['users' => ['provider' => 'users', 'table' => 'password_reset_tokens', 'expire' => 60, 'throttle' => 60]],
];
