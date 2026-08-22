<?php

namespace App\Http\Middleware;

use App\Services\EmpresaAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmpresaSubscription
{
    public function __construct(private readonly EmpresaAccessService $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $message = $request->user() ? $this->access->bloqueio($request->user()) : null;
        if ($message) {
            return response()->json(['message' => $message, 'code' => 'assinatura_bloqueada'], 403);
        }
        return $next($request);
    }
}
