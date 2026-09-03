<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Models\System\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with(['role.permissions'])
            ->where('email', $validated['login'])
            ->orWhere('username', $validated['login'])
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Username atau Email tidak ditemukan.'
            ], 401);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Password salah.'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Akun Anda tidak aktif.'
            ], 403);
        }

        $token = $user->createToken('schoolcms')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => $this->userResponse($user),
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['role.permissions']);

        return response()->json([
            'user' => $this->userResponse($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.'
        ]);
    }

    /**
     * Inline user payload consistent across login/me. `permissions` is the
     * list of effective permission names (from the user's role).
     * Direct user-permissions (pivot permission_user) is skipped if the
     * table does not exist.
     */
    private function userResponse(User $user): array
    {
        $rolePermissions = $user->role?->permissions?->pluck('name')->all() ?? [];
        $effective = array_values(array_unique($rolePermissions));

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'photo' => $user->photo,
            'is_active' => $user->is_active,
            'role' => $user->role?->name,
            'permissions' => $effective,
        ];
    }
}
