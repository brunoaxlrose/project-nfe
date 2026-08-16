<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJsonPayload
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            if (!$request->isJson()) {
                return response()->json(['message' => 'Content-Type application/json obrigatório.'], 415);
            }
            $contentLength = (int) $request->header('Content-Length', 0);
            if ($contentLength > 1024 * 1024) {
                return response()->json(['message' => 'Payload excede o limite de 1 MB.'], 413);
            }
            $data = json_decode($request->getContent(), true);
            if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['message' => 'JSON inválido.'], 400);
            }
            if ($this->containsUnsafeControlData($data)) {
                return response()->json(['message' => 'Payload contém caracteres de controle não permitidos.'], 422);
            }
            $request->replace($data);
        }
        return $next($request);
    }

    private function containsUnsafeControlData(mixed $value): bool
    {
        if (is_string($value)) {
            return str_contains($value, "\0") || preg_match('/[\x01-\x08\x0B\x0C\x0E-\x1F]/u', $value) === 1;
        }
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if ($this->containsUnsafeControlData((string) $key) || $this->containsUnsafeControlData($item)) return true;
            }
        }
        return false;
    }
}
