<?php

declare(strict_types=1);

namespace App\Modules\Identity\UI\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\Actions\AuthActions;
use App\Modules\Identity\Application\DTOs\RegisterCustomerData;
use App\Modules\Identity\Domain\Exceptions\InvalidCredentialsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthActions $authActions,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $payload = $this->authActions->register(new RegisterCustomerData(
            name: (string) $validated['name'],
            email: (string) $validated['email'],
            password: (string) $validated['password'],
            phone: isset($validated['phone']) ? (string) $validated['phone'] : null,
        ));

        return new JsonResponse(['data' => $payload], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $payload = $this->authActions->login(
                (string) $validated['email'],
                (string) $validated['password'],
            );
        } catch (InvalidCredentialsException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 401);
        }

        return new JsonResponse(['data' => $payload], 200);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return new JsonResponse([
            'data' => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'role' => (string) ($user->role ?? 'customer'),
                'phone' => $user->phone !== null ? (string) $user->phone : null,
            ],
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token !== null && method_exists($token, 'delete')) {
            $token->delete();
        }

        return new JsonResponse([
            'message' => 'Đã đăng xuất',
        ], 200);
    }
}
