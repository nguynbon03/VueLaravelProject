<?php

declare(strict_types=1);

namespace App\Core\SharedKernel\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) $request->header('Idempotency-Key', ''));

        if ($key === '') {
            return new JsonResponse([
                'message' => 'Header Idempotency-Key là bắt buộc để chống tạo đơn trùng lặp.',
            ], 422);
        }

        $method = strtoupper($request->method());
        $path = '/'.ltrim($request->path(), '/');
        $requestHash = hash('sha256', (string) $request->getContent());
        $now = now();

        $existing = DB::table('idempotency_keys')
            ->where('key', $key)
            ->where('method', $method)
            ->where('path', $path)
            ->first();

        if ($existing !== null) {
            if ($existing->expires_at !== null && $now->lt($existing->expires_at)) {
                if (! hash_equals((string) $existing->request_hash, $requestHash)) {
                    return new JsonResponse([
                        'message' => 'Idempotency-Key đã được sử dụng cho một payload khác.',
                    ], 409);
                }

                $decoded = json_decode((string) $existing->response_body, true);

                return new JsonResponse(
                    $decoded,
                    (int) $existing->status_code,
                    ['X-Idempotent-Replay' => 'true']
                );
            }

            DB::table('idempotency_keys')->where('id', $existing->id)->delete();
        }

        $response = $next($request);
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 200 && $statusCode < 300) {
            $body = (string) $response->getContent();
            json_decode($body, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                DB::table('idempotency_keys')->updateOrInsert(
                    [
                        'key' => $key,
                        'method' => $method,
                        'path' => $path,
                    ],
                    [
                        'request_hash' => $requestHash,
                        'status_code' => $statusCode,
                        'response_body' => $body,
                        'expires_at' => $now->copy()->addHours(24),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        return $response;
    }
}
