<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAcademicYearRequest;
use App\Http\Requests\Api\UpdateAcademicYearRequest;
use App\Http\Resources\AcademicYearResource;
use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;

class AcademicYearController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = AcademicYear::query();

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where('name', 'LIKE', "%{$search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active') ? 1 : 0);
        }

        $academicYears = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Academic years retrieved successfully',
            'data' => AcademicYearResource::collection($academicYears),
            'meta' => [
                'current_page' => $academicYears->currentPage(),
                'per_page' => $academicYears->perPage(),
                'total' => $academicYears->total(),
                'last_page' => $academicYears->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $academicYear = AcademicYear::find($id);

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'Academic year not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Academic year retrieved successfully',
            'data' => new AcademicYearResource($academicYear),
        ]);
    }

    public function store(StoreAcademicYearRequest $request): JsonResponse
    {
        $academicYear = AcademicYear::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Academic year created successfully',
            'data' => new AcademicYearResource($academicYear),
        ], 201);
    }

    public function update(UpdateAcademicYearRequest $request, int $id): JsonResponse
    {
        $academicYear = AcademicYear::find($id);

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'Academic year not found',
                'data' => null,
            ], 404);
        }

        $academicYear->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Academic year updated successfully',
            'data' => new AcademicYearResource($academicYear),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $academicYear = AcademicYear::find($id);

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'Academic year not found',
                'data' => null,
            ], 404);
        }

        $academicYear->delete();

        return response()->json([
            'success' => true,
            'message' => 'Academic year deleted successfully',
            'data' => null,
        ]);
    }
}
