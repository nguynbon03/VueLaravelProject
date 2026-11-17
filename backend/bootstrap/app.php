<?php

use App\Core\SharedKernel\Infrastructure\Http\Middleware\FreshBearerTokenMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToGroup('api', FreshBearerTokenMiddleware::class);

        $middleware->redirectGuestsTo(
            fn (Request $request): ?string => $request->is('api/*') ? null : null,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthenticationException $e, Request $request): ?JsonResponse {
            if ($request->is('api/*')) {
                return new JsonResponse([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return null;
        });

        /*
         * Never leak SQL, bindings or connection details (host/port/database) to API clients,
         * regardless of APP_DEBUG. The full exception is still logged with a correlation reference.
         */
        $exceptions->render(function (QueryException|PDOException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            $reference = (string) Str::uuid();
            Log::error('Database failure on API request', [
                'reference' => $reference,
                'path' => $request->path(),
                'exception' => $e,
            ]);

            return new JsonResponse([
                'message' => 'Hệ thống đang gặp sự cố, vui lòng thử lại sau.',
                'reference' => $reference,
            ], 500);
        });
    })->create();
