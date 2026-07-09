<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly JwtService $jwt) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::query()->create($data + [
            'role' => UserRole::Customer->value,
        ]);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $this->jwt->issue($user),
            'user' => $user,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $this->jwt->issue($user),
            'user' => $user,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    public function indexUsers(Request $request): JsonResponse
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'created_at', 'updated_at'])
            ->orderBy('name')
            ->paginate(20);

        return response()->json($users);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            abort(422, 'You cannot change your own role.');
        }

        $data = $request->validate([
            'role' => ['required', Rule::in([
                UserRole::ShopOwner->value,
                UserRole::ShopKeeper->value,
            ])],
        ]);

        $user->update([
            'role' => $data['role'],
        ]);

        return response()->json([
            'message' => 'Role updated successfully.',
            'user' => $user->fresh(),
        ]);
    }

    public function logout(): JsonResponse
    {
        return response()->json(['message' => 'Token discarded on client.']);
    }
}
