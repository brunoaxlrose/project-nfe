<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMaster
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->master) {
            return response()->json(['message' => 'Esta área é exclusiva da administração MASTER.'], 403);
        }
        return $next($request);
    }
}
