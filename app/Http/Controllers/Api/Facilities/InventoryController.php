<?php

namespace App\Http\Controllers\Api\Facilities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Facilities\StoreInventoryRequest;
use App\Http\Requests\Api\Facilities\UpdateInventoryRequest;
use App\Http\Resources\Facilities\InventoryResource;
use App\Models\Facilities\Inventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'category' => 'nullable|string|in:stationery,electronics_supplies,cleaning,lab_supplies,office_supplies,other',
            'status' => 'nullable|string|in:active,inactive',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'low_stock' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Inventory::query();

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

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['room_id'])) {
            $query->where('room_id', $validated['room_id']);
        }

        if (isset($validated['low_stock']) && $validated['low_stock']) {
            $query->whereColumn('quantity', '<=', 'minimum_stock');
        }

        $perPage = $validated['per_page'] ?? 10;
        $inventory = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Inventory retrieved successfully',
            'data' => InventoryResource::collection($inventory),
            'meta' => [
                'current_page' => $inventory->currentPage(),
                'per_page' => $inventory->perPage(),
                'total' => $inventory->total(),
                'last_page' => $inventory->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inventory item retrieved successfully',
            'data' => new InventoryResource($inventory),
        ]);
    }

    public function store(StoreInventoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $inventory = DB::connection('mysql')->transaction(function () use ($validated) {
                $exists = Inventory::withTrashed()->where('code', $validated['code'])->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'code' => ['The code has already been taken.'],
                    ]);
                }

                return Inventory::create($validated);
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'code' => ['The code has already been taken.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inventory item created successfully',
            'data' => new InventoryResource($inventory),
        ], 201);
    }

    public function update(UpdateInventoryRequest $request, int $id): JsonResponse
    {
        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();

        try {
            DB::connection('mysql')->transaction(function () use ($inventory, $validated) {
                $code = $validated['code'] ?? $inventory->code;

                $exists = Inventory::withTrashed()->where('code', $code)
                    ->where('id', '!=', $inventory->id)
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'code' => ['The code has already been taken.'],
                    ]);
                }

                $inventory->update($validated);
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'code' => ['The code has already been taken.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inventory item updated successfully',
            'data' => new InventoryResource($inventory),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found',
                'data' => null,
            ], 404);
        }

        $inventory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inventory item deleted successfully',
            'data' => null,
        ]);
    }
}
