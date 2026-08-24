<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePeriodRequest;
use App\Http\Requests\Api\UpdatePeriodRequest;
use App\Http\Resources\PeriodResource;
use App\Models\Period;
use Illuminate\Http\JsonResponse;

class PeriodController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Period::query();

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $periods = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Periods retrieved successfully',
            'data' => PeriodResource::collection($periods),
            'meta' => [
                'current_page' => $periods->currentPage(),
                'per_page' => $periods->perPage(),
                'total' => $periods->total(),
                'last_page' => $periods->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $period = Period::find($id);

        if (!$period) {
            return response()->json([
                'success' => false,
                'message' => 'Period not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Period retrieved successfully',
            'data' => new PeriodResource($period),
        ]);
    }

    public function store(StorePeriodRequest $request): JsonResponse
    {
        $period = Period::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Period created successfully',
            'data' => new PeriodResource($period),
        ], 201);
    }

    public function update(UpdatePeriodRequest $request, int $id): JsonResponse
    {
        $period = Period::find($id);

        if (!$period) {
            return response()->json([
                'success' => false,
                'message' => 'Period not found',
                'data' => null,
            ], 404);
        }

        $period->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Period updated successfully',
            'data' => new PeriodResource($period),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $period = Period::find($id);

        if (!$period) {
            return response()->json([
                'success' => false,
                'message' => 'Period not found',
                'data' => null,
            ], 404);
        }

        $period->delete();

        return response()->json([
            'success' => true,
            'message' => 'Period deleted successfully',
            'data' => null,
        ]);
    }
}
