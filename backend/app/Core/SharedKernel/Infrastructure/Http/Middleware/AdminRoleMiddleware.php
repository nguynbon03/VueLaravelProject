<?php

declare(strict_types=1);

namespace App\Core\SharedKernel\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AdminRoleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return new JsonResponse([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'admin') {
            return new JsonResponse([
                'message' => 'Bạn không có quyền truy cập tài nguyên quản trị (yêu cầu role admin).',
            ], 403);
        }

        return $next($request);
    }
}
