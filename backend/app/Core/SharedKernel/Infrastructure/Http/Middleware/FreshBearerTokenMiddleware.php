<?php

declare(strict_types=1);

namespace App\Core\SharedKernel\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class FreshBearerTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $hasBearerHeader = $request->hasHeader('Authorization');

        if ($hasBearerHeader) {
            Auth::forgetGuards();
        }

        try {
            return $next($request);
        } finally {
            if ($hasBearerHeader) {
                Auth::forgetGuards();
            }
        }
    }
}
