<?php

namespace App\Http\Controllers\Api\Facilities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Facilities\StoreMaintenanceRequest;
use App\Http\Requests\Api\Facilities\UpdateMaintenanceRequest;
use App\Http\Resources\Facilities\MaintenanceResource;
use App\Models\Facilities\Maintenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaintenanceController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'maintenance_type' => 'nullable|string|in:corrective,preventive,emergency,inspection',
            'asset_id' => 'nullable|integer|exists:assets,id',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Maintenance::query();

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['priority'])) {
            $query->where('priority', $validated['priority']);
        }

        if (!empty($validated['maintenance_type'])) {
            $query->where('maintenance_type', $validated['maintenance_type']);
        }

        if (isset($validated['asset_id'])) {
            $query->where('asset_id', $validated['asset_id']);
        }

        if (isset($validated['room_id'])) {
            $query->where('room_id', $validated['room_id']);
        }

        $perPage = $validated['per_page'] ?? 10;
        $maintenance = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance records retrieved successfully',
            'data' => MaintenanceResource::collection($maintenance),
            'meta' => [
                'current_page' => $maintenance->currentPage(),
                'per_page' => $maintenance->perPage(),
                'total' => $maintenance->total(),
                'last_page' => $maintenance->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $maintenance = Maintenance::find($id);

        if (!$maintenance) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance record not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record retrieved successfully',
            'data' => new MaintenanceResource($maintenance),
        ]);
    }

    public function store(StoreMaintenanceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $maintenance = DB::connection('mysql')->transaction(function () use ($validated) {
                $exists = Maintenance::withTrashed()->where('code', $validated['code'])->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'code' => ['The code has already been taken.'],
                    ]);
                }

                return Maintenance::create($validated);
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'code' => ['The code has already been taken.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record created successfully',
            'data' => new MaintenanceResource($maintenance),
        ], 201);
    }

    public function update(UpdateMaintenanceRequest $request, int $id): JsonResponse
    {
        $maintenance = Maintenance::find($id);

        if (!$maintenance) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance record not found',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();

        try {
            DB::connection('mysql')->transaction(function () use ($maintenance, $validated) {
                $code = $validated['code'] ?? $maintenance->code;

                $exists = Maintenance::withTrashed()->where('code', $code)
                    ->where('id', '!=', $maintenance->id)
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'code' => ['The code has already been taken.'],
                    ]);
                }

                $maintenance->update($validated);
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'code' => ['The code has already been taken.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record updated successfully',
            'data' => new MaintenanceResource($maintenance),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $maintenance = Maintenance::find($id);

        if (!$maintenance) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance record not found',
                'data' => null,
            ], 404);
        }

        $maintenance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record deleted successfully',
            'data' => null,
        ]);
    }
}
