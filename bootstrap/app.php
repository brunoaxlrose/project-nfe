<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AuthenticateJwt;
use App\Http\Middleware\EnsureJsonPayload;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\RequireRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withCommands([__DIR__.'/../app/Console/Commands'])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt' => AuthenticateJwt::class,
            'json.payload' => EnsureJsonPayload::class,
            'permission' => RequirePermission::class,
            'role' => RequireRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (!$request->expectsJson()) return null;
            $errors = $exception->errors();
            $messages = collect($errors)->flatten()->map(fn ($message) => translateValidationMessage($message))->values();
            $translatedErrors = collect($errors)->map(fn ($messages) => collect($messages)->map(fn ($message) => translateValidationMessage($message))->values())->all();
            return response()->json(['message' => $messages->first() ?: 'Revise os dados informados.', 'errors' => $translatedErrors], 422);
        });

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (!$request->expectsJson()) {
                return null;
            }

            if ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                $message = match ($status) {
                    403 => 'Você não possui permissão para realizar esta ação.',
                    404 => 'Recurso não encontrado.',
                    405 => 'Operação não permitida para este endereço.',
                    429 => 'Muitas tentativas em pouco tempo. Aguarde e tente novamente.',
                    default => $status >= 500
                        ? 'Não foi possível concluir a operação agora. Tente novamente em alguns instantes.'
                        : 'Não foi possível concluir a solicitação.',
                };

                return response()->json(['message' => $message], $status);
            }

            Log::error('Erro não tratado em uma requisição JSON.', [
                'exception' => $exception,
                'path' => $request->path(),
                'user_id' => $request->user()?->id,
            ]);

            // O Render Free pode não exibir o arquivo de log do Laravel.
            // Envia a exceção para stderr, que aparece diretamente no Runtime Log.
            error_log(sprintf(
                '[FiscalFlow] %s: %s em %s:%d | rota=%s',
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $request->path(),
            ));

            return response()->json([
                'message' => 'Não foi possível concluir a operação agora. Tente novamente em alguns instantes.',
            ], 500);
        });
    })
    ->create();

function translateValidationMessage(string $message): string
{
    $translated = str_replace(['The cnpj field must be 14 digits.', 'The cep field must be 8 digits.', 'The password field must be at least 8 characters.', 'The password field must contain at least one symbol.', 'The password field must contain at least one uppercase and one lowercase letter.', 'The password field must contain at least one number.', 'field is required.', 'The field must be a valid email address.'], ['Informe um CNPJ válido com 14 números.', 'Informe um CEP válido com 8 números.', 'A senha deve ter pelo menos 8 caracteres.', 'A senha deve conter pelo menos um símbolo.', 'A senha deve conter letras maiúsculas e minúsculas.', 'A senha deve conter pelo menos um número.', 'é obrigatório.', 'Informe um e-mail válido.'], $message);
    $translated = preg_replace('/^The (.+) é obrigatório\.$/u', 'O campo $1 é obrigatório.', $translated) ?: $translated;
    $translated = preg_replace('/^The (.+) field must be a number\.$/u', 'O campo $1 deve ser numérico.', $translated) ?: $translated;
    return $translated;
}
