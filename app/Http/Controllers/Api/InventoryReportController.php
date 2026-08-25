<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InventoryReportController extends Controller
{
    public function stockSummary(): JsonResponse
    {
        $items = DB::table('inventories')
            ->whereNull('deleted_at')
            ->orderBy('quantity', 'asc')
            ->get([
                'id',
                'code',
                'name',
                'category',
                'unit',
                'quantity',
                'minimum_stock',
                'location',
            ])
            ->map(function ($row) {
                $row->quantity = (int) $row->quantity;
                $row->minimum_stock = (int) $row->minimum_stock;
                $row->stock_status = $row->quantity <= $row->minimum_stock
                    ? 'low'
                    : ($row->quantity <= $row->minimum_stock * 2 ? 'warning' : 'healthy');
                return $row;
            });

        $categories = DB::table('inventories')
            ->whereNull('deleted_at')
            ->groupBy('category')
            ->orderBy('category')
            ->get([
                'category',
                DB::raw('COUNT(*) AS total_items'),
            ])
            ->map(fn ($row) => [
                'category' => $row->category,
                'total_items' => (int) $row->total_items,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Inventory stock report retrieved successfully',
            'data' => [
                'items' => $items,
                'totals' => [
                    'total_items' => $items->count(),
                    'total_low_stock' => $items->where('stock_status', 'low')->count(),
                    'categories' => $categories,
                ],
            ],
        ]);
    }

    public function movementSummary(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $query = DB::table('stock_movements')
            ->when(!empty($validated['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $validated['date_from']))
            ->when(!empty($validated['date_to']), fn ($q) => $q->whereDate('created_at', '<=', $validated['date_to']));

        // totals per type: dynamic keys from whatever types exist (empty-safe)
        $byType = (clone $query)
            ->selectRaw('type, COALESCE(SUM(quantity), 0) AS total_quantity')
            ->groupBy('type')
            ->pluck('total_quantity', 'type');

        $totals = [];
        foreach ($byType as $type => $total) {
            $totals[$type] = (int) $total;
        }

        $recent = (clone $query)
            ->leftJoin('inventories', 'inventories.id', '=', 'stock_movements.inventory_id')
            ->orderBy('stock_movements.created_at', 'desc')
            ->limit(50)
            ->get([
                'stock_movements.id',
                'stock_movements.inventory_id',
                'inventories.name AS inventory_name',
                'stock_movements.type',
                'stock_movements.quantity',
                'stock_movements.adjustment_type',
                'stock_movements.notes',
                'stock_movements.created_at',
            ])
            ->map(fn ($row) => [
                'id' => $row->id,
                'inventory_id' => $row->inventory_id,
                'inventory_name' => $row->inventory_name,
                'type' => $row->type,
                'quantity' => (int) $row->quantity,
                'adjustment_type' => $row->adjustment_type,
                'notes' => $row->notes,
                'created_at' => $row->created_at,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Stock movement report retrieved successfully',
            'data' => [
                'totals_by_type' => $totals,
                'recent' => $recent,
            ],
        ]);
    }
}
