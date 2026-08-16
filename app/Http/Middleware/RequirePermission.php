<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user || !$user->active) {
            return response()->json([
                'message' => 'Sua sessão não é mais válida. Entre novamente para continuar.',
            ], 401);
        }

        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if (
                $parameter instanceof Model
                && $parameter->getAttribute('id_empresa') !== null
                && (int) $parameter->getAttribute('id_empresa') !== (int) $user->id_empresa
            ) {
                abort(404);
            }
        }

        if ($permissions !== [] && !$user->temAlgumaPermissao($permissions)) {
            return response()->json([
                'message' => 'Você não possui permissão para realizar esta ação.',
            ], 403);
        }

        return $next($request);
    }
}
