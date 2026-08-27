<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Academic\StoreAcademicYearRequest;
use App\Http\Requests\Api\Academic\UpdateAcademicYearRequest;
use App\Http\Resources\Academic\AcademicYearResource;
use App\Models\Academic\AcademicYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
        $validated = $request->validated();

        $academicYear = DB::connection('mysql')->transaction(function () use ($validated) {
            $activate = (bool) ($validated['is_active'] ?? false);

            if ($activate) {
                AcademicYear::query()->update(['is_active' => false]);
            }

            $record = AcademicYear::create($validated);

            if ($activate) {
                $record->update(['is_active' => true]);
            }

            return $record;
        });

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

        $validated = $request->validated();

        DB::connection('mysql')->transaction(function () use ($academicYear, $validated) {
            $activate = array_key_exists('is_active', $validated)
                && (bool) $validated['is_active'];

            if ($activate) {
                AcademicYear::query()
                    ->where('id', '!=', $academicYear->id)
                    ->update(['is_active' => false]);
            }

            $academicYear->update($validated);
        });

        return response()->json([
            'success' => true,
            'message' => 'Academic year updated successfully',
            'data' => new AcademicYearResource($academicYear->refresh()),
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
