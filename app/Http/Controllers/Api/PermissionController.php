<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePermissionRequest;
use App\Http\Requests\Api\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Permission::query();

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $perPage = $validated['per_page'] ?? 10;
        $permissions = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Permissions retrieved successfully',
            'data' => PermissionResource::collection($permissions),
            'meta' => [
                'current_page' => $permissions->currentPage(),
                'per_page' => $permissions->perPage(),
                'total' => $permissions->total(),
                'last_page' => $permissions->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission retrieved successfully',
            'data' => new PermissionResource($permission),
        ]);
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = Permission::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Permission created successfully',
            'data' => new PermissionResource($permission),
        ], 201);
    }

    public function update(UpdatePermissionRequest $request, int $id): JsonResponse
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
                'data' => null,
            ], 404);
        }

        $permission->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Permission updated successfully',
            'data' => new PermissionResource($permission),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
                'data' => null,
            ], 404);
        }

        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission deleted successfully',
            'data' => null,
        ]);
    }
}
