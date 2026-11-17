<?php

declare(strict_types=1);

namespace App\Modules\Identity\UI\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\Contracts\IdentityAuthPort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class AdminUserController extends Controller
{
    public function __construct(
        private readonly IdentityAuthPort $identityPort,
    ) {}

    public function index(): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->identityPort->listAllUsersWithStats(),
        ], 200);
    }

    public function updateRole(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(['customer', 'admin'])],
        ]);

        $updated = $this->identityPort->updateRole($id, (string) $validated['role']);
        if ($updated === null) {
            return new JsonResponse(['message' => 'Không tìm thấy người dùng.'], 404);
        }

        return new JsonResponse([
            'data' => [
                'id' => $updated->id,
                'name' => $updated->name,
                'email' => $updated->email,
                'role' => $updated->role,
                'phone' => $updated->phone,
            ],
        ], 200);
    }
}
