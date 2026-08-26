<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Finance\StoreFeeTypeRequest;
use App\Http\Requests\Api\Finance\UpdateFeeTypeRequest;
use App\Http\Resources\Finance\FeeTypeResource;
use App\Models\Finance\FeeType;
use Illuminate\Http\JsonResponse;

class FeeTypeController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = FeeType::query();

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where('name', 'LIKE', "%{$q}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active') ? 1 : 0);
        }

        $feeTypes = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Fee types retrieved successfully',
            'data' => FeeTypeResource::collection($feeTypes),
            'meta' => [
                'current_page' => $feeTypes->currentPage(),
                'per_page' => $feeTypes->perPage(),
                'total' => $feeTypes->total(),
                'last_page' => $feeTypes->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $feeType = FeeType::find($id);

        if (!$feeType) {
            return response()->json([
                'success' => false,
                'message' => 'Fee type not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fee type retrieved successfully',
            'data' => new FeeTypeResource($feeType),
        ]);
    }

    public function store(StoreFeeTypeRequest $request): JsonResponse
    {
        $feeType = FeeType::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Fee type created successfully',
            'data' => new FeeTypeResource($feeType),
        ], 201);
    }

    public function update(UpdateFeeTypeRequest $request, int $id): JsonResponse
    {
        $feeType = FeeType::find($id);

        if (!$feeType) {
            return response()->json([
                'success' => false,
                'message' => 'Fee type not found',
                'data' => null,
            ], 404);
        }

        $feeType->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Fee type updated successfully',
            'data' => new FeeTypeResource($feeType),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $feeType = FeeType::find($id);

        if (!$feeType) {
            return response()->json([
                'success' => false,
                'message' => 'Fee type not found',
                'data' => null,
            ], 404);
        }

        $feeType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fee type deleted successfully',
            'data' => null,
        ]);
    }
}
