<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\EmpresaAccessService;

class RequirePermission
{
    public function __construct(private readonly EmpresaAccessService $access) {}

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

        foreach ($permissions as $permission) {
            if (!$this->access->moduloPermitido($user, $permission)) {
                return response()->json([
                    'message' => 'Este módulo não faz parte do plano contratado pela empresa.',
                    'code' => 'modulo_nao_contratado',
                ], 403);
            }
        }

        return $next($request);
    }
}
