<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(function ($user, string $ability): ?bool {
            if (!method_exists($user, 'temPermissao')) {
                return null;
            }

            return $user->temPermissao($ability) ? true : null;
        });
    }
}
