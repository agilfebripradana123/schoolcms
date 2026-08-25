<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreStaffRequest;
use App\Http\Requests\Api\UpdateStaffRequest;
use App\Http\Resources\StaffResource;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;

class StaffController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Staff::query();

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where('name', 'LIKE', "%{$q}%");
        }

        if ($request->filled('position')) {
            $query->where('position', $request->input('position'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active') ? 1 : 0);
        }

        $staff = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Staff retrieved successfully',
            'data' => StaffResource::collection($staff),
            'meta' => [
                'current_page' => $staff->currentPage(),
                'per_page' => $staff->perPage(),
                'total' => $staff->total(),
                'last_page' => $staff->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $staff = Staff::find($id);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Staff retrieved successfully',
            'data' => new StaffResource($staff),
        ]);
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        $staff = Staff::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Staff created successfully',
            'data' => new StaffResource($staff),
        ], 201);
    }

    public function update(UpdateStaffRequest $request, int $id): JsonResponse
    {
        $staff = Staff::find($id);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff not found',
                'data' => null,
            ], 404);
        }

        $staff->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Staff updated successfully',
            'data' => new StaffResource($staff),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $staff = Staff::find($id);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff not found',
                'data' => null,
            ], 404);
        }

        $staff->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff deleted successfully',
            'data' => null,
        ]);
    }
}
