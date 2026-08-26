<?php

namespace App\Http\Controllers\Api\Facilities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Facilities\StoreAssetRequest;
use App\Http\Requests\Api\Facilities\UpdateAssetRequest;
use App\Http\Resources\Facilities\AssetResource;
use App\Models\Facilities\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'category' => 'nullable|string|in:electronics,furniture,lab_equipment,sports,teaching_aids,office,other',
            'condition' => 'nullable|string|in:good,fair,poor,damaged',
            'status' => 'nullable|string|in:active,inactive',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Asset::query();

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if (!empty($validated['condition'])) {
            $query->where('condition', $validated['condition']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['room_id'])) {
            $query->where('room_id', $validated['room_id']);
        }

        $perPage = $validated['per_page'] ?? 10;
        $assets = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Assets retrieved successfully',
            'data' => AssetResource::collection($assets),
            'meta' => [
                'current_page' => $assets->currentPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
                'last_page' => $assets->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $asset = Asset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Asset retrieved successfully',
            'data' => new AssetResource($asset),
        ]);
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $asset = DB::connection('mysql')->transaction(function () use ($validated) {
                $exists = Asset::withTrashed()->where('code', $validated['code'])->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'code' => ['The code has already been taken.'],
                    ]);
                }

                return Asset::create($validated);
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'code' => ['The code has already been taken.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Asset created successfully',
            'data' => new AssetResource($asset),
        ], 201);
    }

    public function update(UpdateAssetRequest $request, int $id): JsonResponse
    {
        $asset = Asset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();

        try {
            DB::connection('mysql')->transaction(function () use ($asset, $validated) {
                $code = $validated['code'] ?? $asset->code;

                $exists = Asset::withTrashed()->where('code', $code)
                    ->where('id', '!=', $asset->id)
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'code' => ['The code has already been taken.'],
                    ]);
                }

                $asset->update($validated);
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'code' => ['The code has already been taken.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Asset updated successfully',
            'data' => new AssetResource($asset),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $asset = Asset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found',
                'data' => null,
            ], 404);
        }

        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset deleted successfully',
            'data' => null,
        ]);
    }
}
