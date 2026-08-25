<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreStockInRequest;
use App\Http\Requests\Api\StoreStockOutRequest;
use App\Http\Requests\Api\StoreAdjustmentRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementController extends Controller
{
    public function stockIn(StoreStockInRequest $request, int $inventoryId): JsonResponse
    {
        $inventory = Inventory::find($inventoryId);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();

        $movement = DB::connection('mysql')->transaction(function () use ($inventory, $validated) {
            $inventory = Inventory::query()
                ->lockForUpdate()
                ->find($inventory->id);

            $inventory->quantity += $validated['quantity'];
            $inventory->save();

            return StockMovement::create([
                'inventory_id' => $inventory->id,
                'type' => 'stock_in',
                'quantity' => $validated['quantity'],
                'adjustment_type' => null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $validated['created_by'] ?? null,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Stock in recorded successfully',
            'data' => new StockMovementResource($movement),
        ], 201);
    }

    public function stockOut(StoreStockOutRequest $request, int $inventoryId): JsonResponse
    {
        $inventory = Inventory::find($inventoryId);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();

        if ($validated['quantity'] > $inventory->quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['The requested quantity (' . $validated['quantity'] . ') exceeds available stock (' . $inventory->quantity . ').'],
            ]);
        }

        $movement = DB::connection('mysql')->transaction(function () use ($inventory, $validated) {
            $inventory = Inventory::query()
                ->lockForUpdate()
                ->find($inventory->id);

            if ($validated['quantity'] > $inventory->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['The requested quantity exceeds available stock.'],
                ]);
            }

            $inventory->quantity -= $validated['quantity'];
            $inventory->save();

            return StockMovement::create([
                'inventory_id' => $inventory->id,
                'type' => 'stock_out',
                'quantity' => $validated['quantity'],
                'adjustment_type' => null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $validated['created_by'] ?? null,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Stock out recorded successfully',
            'data' => new StockMovementResource($movement),
        ], 201);
    }

    public function adjustment(StoreAdjustmentRequest $request, int $inventoryId): JsonResponse
    {
        $inventory = Inventory::find($inventoryId);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();

        if ($validated['adjustment_type'] === 'decrease' && $validated['quantity'] > $inventory->quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['The adjustment quantity (' . $validated['quantity'] . ') exceeds available stock (' . $inventory->quantity . ').'],
            ]);
        }

        $movement = DB::connection('mysql')->transaction(function () use ($inventory, $validated) {
            $inventory = Inventory::query()
                ->lockForUpdate()
                ->find($inventory->id);

            if ($validated['adjustment_type'] === 'decrease' && $validated['quantity'] > $inventory->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['The adjustment quantity exceeds available stock.'],
                ]);
            }

            if ($validated['adjustment_type'] === 'increase') {
                $inventory->quantity += $validated['quantity'];
            } else {
                $inventory->quantity -= $validated['quantity'];
            }

            $inventory->save();

            return StockMovement::create([
                'inventory_id' => $inventory->id,
                'type' => 'adjustment',
                'quantity' => $validated['quantity'],
                'adjustment_type' => $validated['adjustment_type'],
                'notes' => $validated['notes'],
                'created_by' => $validated['created_by'] ?? null,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Stock adjustment recorded successfully',
            'data' => new StockMovementResource($movement),
        ], 201);
    }

    public function movements(\Illuminate\Http\Request $request, int $inventoryId): JsonResponse
    {
        $inventory = Inventory::find($inventoryId);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found',
                'data' => null,
            ], 404);
        }

        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $perPage = $validated['per_page'] ?? 10;
        $movements = StockMovement::where('inventory_id', $inventoryId)
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Stock movements retrieved successfully',
            'data' => StockMovementResource::collection($movements),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
                'last_page' => $movements->lastPage(),
            ],
        ]);
    }
}
